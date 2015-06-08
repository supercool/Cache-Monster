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

class CacheMonsterController extends BaseController
{

	protected $allowAnonymous = array('actionCrawlAndWarm');

	/**
	 * Update our url cache and force run the warming Task
	 *
	 * This should really be used with a fully purged Varnish
	 */
	public function actionCrawlAndWarm()
	{

		// Delete all the template caches!
		craft()->templateCache->deleteAllCaches();

		// Crawl the sitemap
		$paths = craft()->cacheMonster->crawlSitemapForPaths();

		// Check we have something and make warmer if we do
		if ($paths)
		{
			craft()->cacheMonster->makeTask('CacheMonster_Warm', $paths);
		}

		// Run any pending tasks
		if (!craft()->tasks->isTaskRunning())
		{
			// Is there a pending task?
			$task = craft()->tasks->getNextPendingTask();

			if ($task)
			{
				// Attempt to close the connection if this is an Ajax request
				if (craft()->request->isAjaxRequest())
				{
					craft()->request->close();
				}

				// Start running tasks
				craft()->tasks->runPendingTasks();
			}
		}

		// Exit
		craft()->end();

	}

}
