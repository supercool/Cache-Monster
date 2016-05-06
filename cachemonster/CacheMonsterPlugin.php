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

class CacheMonsterPlugin extends BasePlugin
{

	// Properties
	// =========================================================================


	// Public Methods
	// =========================================================================

	public function getName()
	{
		return Craft::t('CacheMonster');
	}

	public function getVersion()
	{
		return '2.0.0';
	}

	public function getDeveloper()
	{
		return 'Supercool';
	}

	public function getDeveloperUrl()
	{
		return 'http://plugins.supercooldesign.co.uk';
	}

	public function init()
	{

	}

	/**
	 * @return CacheMonsterTwigExtension
	 */
	public function addTwigExtension()
	{
		Craft::import('plugins.cachemonster.twigextensions.*');
		return new CacheMonsterTwigExtension();
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('cacheMonster/settings', array(
			'settings' => $this->getSettings()
		));
	}

	// Protected Methods
	// =========================================================================

	protected function defineSettings()
	{
		return array(
			//
		);
	}

	// Private Methods
	// =========================================================================

}
