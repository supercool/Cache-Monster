<?php
namespace Craft;

/**
 * Varnish by Supercool
 *
 * @package   Varnish
 * @author    Josh Angell
 * @copyright Copyright (c) 2015, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class VarnishController extends BaseController
{

	protected $allowAnonymous = array('actionCrawlPurgeAndWarm');

	/**
	 * Update our url cache and force run the purging and warming Tasks
	 */
	public function actionCrawlPurgeAndWarm()
	{

		// Crawl
		$paths = craft()->varnish->crawlSitemapForPaths();

		if (!$paths)
		{
			$paths = craft()->cache->get('varnishPaths');
		}

		// Check we have something either from crawling or stale
		// and make our Tasks
		if ($paths)
		{
			craft()->varnish->makeTask('Varnish_Purge', $paths);
			craft()->varnish->makeTask('Varnish_Warm', $paths);
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
