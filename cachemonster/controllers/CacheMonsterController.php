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

		// Crawl the sitemap
		$paths = craft()->cacheMonster->crawlSitemapForPaths();

		// Check we have something
		if (!$paths)
		{
			throw new Exception(Craft::t('No paths found in “{url}”.', array('url' => UrlHelper::getSiteUrl('sitemap.xml'))));
		}

		// Delete all the template caches!
		craft()->templateCache->deleteAllCaches();

		// Make task to warm the template caches
		craft()->cacheMonster->makeTask('CacheMonster_Warm', $paths);

		// Run any pending tasks
		craft()->runController('tasks/runPendingTasks');

	}

	/**
	 * Updates the cached templates for the given cache keys.
	 *
	 * @return null
	 */
	public function actionWarmByKeys()
	{

		$cacheKeys = craft()->request->getRequiredParam('cacheKeys');

		if (!is_array($cacheKeys))
		{
			$cacheKeys = array($cacheKeys);
		}

		// Get the template paths for the caches with the given cache keys
		$query = craft()->db->createCommand()
			->selectDistinct('path')
			->from('templatecaches')
			->where(array('in', 'cacheKey', $cacheKeys));

		$paths = $query->queryColumn();

		// Check we have something
		if (!$paths)
		{
			throw new Exception(Craft::t('No cached templates found for the given keys.'));
		}

		// Delete the template caches
		craft()->templateCache->deleteCachesByKey($cacheKeys);

		// Make task to warm the template caches
		craft()->cacheMonster->makeTask('CacheMonster_Warm', $paths);

		// Run any pending tasks
		craft()->runController('tasks/runPendingTasks');

	}

}
