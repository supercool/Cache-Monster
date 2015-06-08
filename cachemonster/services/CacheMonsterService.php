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

	/**
	 * [purgeElementById description]
	 *
	 * @method getPathsToPurge
	 * @param  int           $elementId the elementId of the element we want purging
	 * @return array                    an array of paths to purge
	 */
	public function getPathsToPurge($elementId)
	{

		// Get the cache ids that relate to this element
		$query = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from('templatecacheelements')
			->where('elementId = :elementId', array(':elementId' => $elementId));

		$cacheIds = $query->queryColumn();

		if ($cacheIds)
		{

			// Get the paths that those caches related to
			$query = craft()->db->createCommand()
				->selectDistinct('path')
				->from('templatecaches')
				->where(array('in', 'id', $cacheIds));

			$paths = $query->queryColumn();

			// Return an array of them
			if ($paths)
			{

				if ( ! is_array($paths) )
				{
					$paths = array($paths);
				}

				return $paths;
			}
			else
			{
				return false;
			}

		}
		else
		{
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

}
