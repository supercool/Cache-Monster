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

	protected $allowAnonymous = array('actionCrawlAndWarm');

	/**
	 * Update our url cache and force run the warming Task
	 *
	 * This should really be used with a fully purged Varnish
	 */
	public function actionCrawlAndWarm()
	{

		// Crawl
		$paths = craft()->varnish->crawlSitemapForPaths();

		// Check we have something
		if ($paths)
		{
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
