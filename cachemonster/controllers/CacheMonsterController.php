<?php
namespace Craft;

/**
 * CacheMonster by Supercool
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonsterController extends BaseController
{

	protected $allowAnonymous = array('actionClearCache');

	/**
	 * Index
	 */
	public function actionIndex()
	{
		$variables['caches'] = craft()->cacheMonster_templateCache->getAllTemplateCaches();

		$this->renderTemplate('cachemonster/_index', $variables);
	}

	/**
	 * Deletes all the template caches
	 */
	public function actionDeleteAllCaches()
	{
		$this->requirePostRequest();
		craft()->cacheMonster_templateCache->deleteAllCaches();
		craft()->userSession->setNotice(Craft::t('Caches deleted.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Deletes a specific template cache
	 */
	public function actionDeleteCache()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$cacheId = craft()->request->getRequiredPost('id');

		craft()->cacheMonster_templateCache->deleteCacheById($cacheId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Clear the cache
	 */
	public function actionClearCache()
	{

		// TODO: Should probably provide some kind of security layer here like a
		//       key/string proveded as a required plugin setting.

		// Delete all the template caches!
		craft()->cacheMonster_templateCache->deleteAllCaches();
		
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
