<?php
namespace Craft;

/**
 * CacheMonster by Supercool
 *
 * Gets the caches that are about to be deleted by the DeleteStaleTemplateCachesTask
 * and returns the paths for them.
 *
 * Basically nicked the logic from the DeleteStaleTemplateCachesTask
 * to work out which caches we are dealing with.
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2015, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_CachePathsTask extends BaseTask
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

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Getting urls to purge');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{

		$elementId = $this->getSettings()->elementId;

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

		// NOTE: Always returning 0 right now ...
		CacheMonsterPlugin::log('Running.', LogLevel::Info);
		CacheMonsterPlugin::log('Count: '.$totalRows, LogLevel::Info);

		return $totalRows;
	}

	/**
	 * @inheritDoc ITask::runStep()
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{

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
				return true;
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

		// Get the cacheIds that are directly applicable to these elements
		$query = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from('templatecacheelements')
			->where(array('in', 'elementId', $this->_elementIds));

		$this->_cacheIdsToBeDeleted = array_merge($this->_cacheIdsToBeDeleted, $query->queryColumn());

		if ($this->_cacheIdsToBeDeleted)
		{

			// Get the paths that those caches related to
			$query = craft()->db->createCommand()
				->selectDistinct('path')
				->from('templatecaches')
				->where(array('in', 'id', $this->_cacheIdsToBeDeleted));

			$paths = $query->queryColumn();

			// Make some Tasks with those paths
			if ($paths) {

				if (!is_array($paths)) {
					$paths = array($paths);
				}

				// Store them in the cache so we can get at them later on, merging with
				// any already there (i.e. this Task has had elements appended to it)
				$cachedPaths = craft()->cache->get("cacheMonsterPaths");

				if ($cachedPaths) {
					$paths = array_merge($cachedPaths, $paths);
				}

				$paths = array_unique($paths);

				craft()->cache->set("cacheMonsterPaths", $paths);
			}

		}

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'elementId' => AttributeType::Mixed
		);
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
