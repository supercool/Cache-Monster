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

class CacheMonsterPlugin extends BasePlugin
{

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_settings;

	// Public Methods
	// =========================================================================

	public function getName()
	{
		return Craft::t('CacheMonster');
	}

	public function getVersion()
	{
		return '1.0.0';
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

		/**
		 * Get plugin settings
		 */
		$plugin = craft()->plugins->getPlugin('cachemonster');
		$this->_settings = $plugin->getSettings();

		/**
		 * Before we save, grab the paths that are going to be purged
		 * and save them to a cache
		 */
		craft()->on('elements.onBeforeSaveElement', function(Event $event)
		{

			// Don’t bother doing anything if neither warming or purging is needed
			if ($this->_settings['varnish'] || $this->_settings['warm'])
			{

				// Get the element
				$element = $event->params['element'];

				// Clear our cacheMonsterPaths cache, just in case
				craft()->cache->delete("cacheMonsterPaths-{$element->id}-{$element->locale}");

				// Get the paths we need
				$paths = craft()->cacheMonster->getPaths($element);

				if ($paths)
				{

					// Store them in the cache so we can get them after
					// the element has actually saved
					craft()->cache->set("cacheMonsterPaths-{$element->id}-{$element->locale}", $paths);

				}
			}

		});


		/**
		 * After the element has saved run the purging and warming tasks
		 */
		craft()->on('elements.onSaveElement', function(Event $event)
		{

			// Get the element
			$element = $event->params['element'];

			// Get the paths out of the cache for that element
			$paths = craft()->cache->get("cacheMonsterPaths-{$element->id}-{$element->locale}");

			// Remove this, as it might cause issues if its used again
			craft()->cache->delete("cacheMonsterPaths-{$element->id}-{$element->locale}");

			// Use those paths to purge (if on) and warm
			if ($paths)
			{

				if ($this->_settings['varnish'])
				{
					craft()->cacheMonster->makeTask('CacheMonster_Purge', $paths);
				}

				if ($this->_settings['warm'])
				{
					craft()->cacheMonster->makeTask('CacheMonster_Warm', $paths);
				}

			}

		});

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
			'varnish' => array(AttributeType::Bool, 'default' => false),
			'warm' => array(AttributeType::Bool, 'default' => true)
		);
	}

}
