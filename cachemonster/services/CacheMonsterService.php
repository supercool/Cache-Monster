<?php
namespace Craft;

/**
 * CacheMonster by Supercool
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2015, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonsterService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_elementIds;

	/**
	 * @var
	 */
	private $_elementType;

	/**
	 * @var
	 */
	private $_batch;

	/**
	 * @var
	 */
	private $_batchRows;

	/**
	 * @var
	 */
	private $_noMoreRows;

	/**
	 * @var
	 */
	private $_cacheIdsToBeDeleted;

	/**
	 * @var
	 */
	private $_totalCriteriaRowsToBeDeleted;

	/**
	 * Gets the caches that are about to be deleted by the DeleteStaleTemplateCachesTask
	 * and returns the paths for them.
	 *
	 * Basically nicked the logic from the DeleteStaleTemplateCachesTask
	 * to work out which caches we are dealing with.
	 *
	 * @method getPaths
	 * @param  int           $element the element we want to purge
	 * @return array                  an array of paths to purge
	 */
	public function getPaths($element)
	{
		$elementId = $element->id;

		// What type of element(s) are we dealing with?
		$this->_elementType = craft()->elements->getElementTypeById($elementId);
		if (!$this->_elementType)
		{
			return 0;
		}

		if (is_array($elementId))
		{
			$this->_elementIds = $elementId;
		}
		else
		{
			$this->_elementIds = array($elementId);
		}

		// Figure out how many rows we're dealing with
		$totalRows = $this->_getQuery()->count('id');
		$this->_batch = 0;
		$this->_noMoreRows = false;
		$this->_cacheIdsToBeDeleted = array();
		$this->_totalCriteriaRowsToBeDeleted = 0;

		// Loop each of the relavent rows in the `templatecachecriteria` table
		for ($i=0; $i < $totalRows; $i++) {

			// Do we need to grab a fresh batch?
			if (empty($this->_batchRows))
			{
				if (!$this->_noMoreRows)
				{
					$this->_batch++;
					$this->_batchRows = $this->_getQuery()
						->order('id')
						->offset(100*($this->_batch-1) - $this->_totalCriteriaRowsToBeDeleted)
						->limit(100)
						->queryAll();
					// Still no more rows?
					if (!$this->_batchRows)
					{
						$this->_noMoreRows = true;
					}
				}
				if ($this->_noMoreRows)
				{
					return;
				}
			}

			$row = array_shift($this->_batchRows);
			// Have we already deleted this cache?
			if (in_array($row['cacheId'], $this->_cacheIdsToBeDeleted))
			{
				$this->_totalCriteriaRowsToBeDeleted++;
			}
			else
			{
				// Create an ElementCriteriaModel that resembles the one that led to this query
				$params = JsonHelper::decode($row['criteria']);
				$criteria = craft()->elements->getCriteria($row['type'], $params);
				// Chance overcorrecting a little for the sake of templates with pending elements,
				// whose caches should be recreated (see http://craftcms.stackexchange.com/a/2611/9)
				$criteria->status = null;
				// See if any of the updated elements would get fetched by this query
				if (array_intersect($criteria->ids(), $this->_elementIds))
				{
					// Delete this cache
					$this->_cacheIdsToBeDeleted[] = $row['cacheId'];
					$this->_totalCriteriaRowsToBeDeleted++;
				}
			}

		}

		// Get the cacheIds that are directly applicable to this element
		$query = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from('templatecacheelements')
			->where('elementId = :elementId', array(':elementId' => $elementId));

		$this->_cacheIdsToBeDeleted = array_merge($this->_cacheIdsToBeDeleted, $query->queryColumn());

		if ($this->_cacheIdsToBeDeleted)
		{

			// Get the paths that those caches related to
			$query = craft()->db->createCommand()
				->selectDistinct('path')
				->from('templatecaches')
				->where(array('in', 'id', $this->_cacheIdsToBeDeleted));

			$paths = $query->queryColumn();

			// Return an array of them
			if ($paths) {

				if (!is_array($paths)) {
					$paths = array($paths);
				}

				return $paths;
			} else {
				return false;
			}

		} else {
			return false;
		}

	}


	/**
	 * Gets the sitemap then caches and returns an array of the paths found in it
	 *
	 *  TODO: let user set sitemap location(s) in the cp, default to /sitemap.xml
	 *
	 * @method crawlSitemapForPaths
	 * @return array               an array of $paths
	 */
	public function crawlSitemapForPaths()
	{

		// This might be heavy, probably not but better safe than sorry
		craft()->config->maxPowerCaptain();

		$paths = array();

		// Get the (one day specified) sitemap
		$client = new \Guzzle\Http\Client();
		$response = $client->get(UrlHelper::getSiteUrl('sitemap.xml'))->send();

		// Get the xml and add each url to the $paths array
		if ( $response->isSuccessful() )
		{
			$xml = $response->xml();

			foreach ($xml->url as $url)
			{
				$parts = parse_url((string)$url->loc);
				$paths[] = 'site:' . ltrim($parts['path'], '/');
			}
		}

		// Check $paths is unique
		$paths = array_unique($paths);

		// Return the actual paths
		return $paths;

	}


	/**
	 * Regusters a Task with Craft, taking into account if there
	 * is already one pending
	 *
	 * @method makeTask
	 * @param  string    $taskName   the name of the Task you want to register
	 * @param  array     $paths      an array of paths that should go in that Tasks settings
	 */
	public function makeTask($taskName, $paths)
	{

		// If there are any pending tasks, just append the paths to it
		$task = craft()->tasks->getNextPendingTask($taskName);

		if ($task && is_array($task->settings))
		{
			$settings = $task->settings;

			if (!is_array($settings['paths']))
			{
				$settings['paths'] = array($settings['paths']);
			}

			if (is_array($paths))
			{
				$settings['paths'] = array_merge($settings['paths'], $paths);
			}
			else
			{
				$settings['paths'][] = $paths;
			}

			// Make sure there aren't any duplicate paths
			$settings['paths'] = array_unique($settings['paths']);

			// Set the new settings and save the task
			$task->settings = $settings;
			craft()->tasks->saveTask($task, false);
		}
		else
		{
			craft()->tasks->createTask($taskName, null, array(
				'paths' => $paths
			));
		}

	}

	// Private Methods
	// =========================================================================
	/**
	 * Returns a DbCommand object for selecting criteria that could be dropped by this task.
	 *
	 * @return DbCommand
	 */
	private function _getQuery()
	{
		$query = craft()->db->createCommand()
			->from('templatecachecriteria');
		if (is_array($this->_elementType))
		{
			$query->where(array('in', 'type', $this->_elementType));
		}
		else
		{
			$query->where('type = :type', array(':type' => $this->_elementType));
		}
		return $query;
	}

}
