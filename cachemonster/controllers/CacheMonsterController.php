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

}
