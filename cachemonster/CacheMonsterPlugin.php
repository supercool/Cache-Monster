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
		return '0.9';
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

			// Get the element ID
			$elementId = $event->params['element']->id;

			// If we have an element, go ahead and get its paths
			if ( $elementId )
			{

				// Clear our cacheMonsterPaths cache, just in case
				craft()->cache->delete('cacheMonsterPaths-'.$elementId);

				// Get the paths we need
				$paths = craft()->cacheMonster->getPaths($elementId);

				if ($paths)
				{

					// Store them in the cache so we can get them after
					// the element has actually saved
					craft()->cache->set('cacheMonsterPaths-'.$elementId, $paths);

				}

			}

		});


		/**
		 * After the element has saved run the purging and warming tasks
		 */
		craft()->on('elements.onSaveElement', function(Event $event)
		{

			// Get the element ID
			$elementId = $event->params['element']->id;

			if ($elementId)
			{

				// Get the paths out of the cache for that element
				$paths = craft()->cache->get('cacheMonsterPaths-'.$elementId);

				// Remove this, as it might cause issues if its used again
				craft()->cache->delete('cacheMonsterPaths-'.$elementId);

				// Use those paths to purge (if on) and warm
				if ($paths)
				{

					if ($this->_settings['varnish'])
					{
						craft()->cacheMonster->makeTask('CacheMonster_Purge', $paths);
					}

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
			'varnish' => array(AttributeType::Bool, 'default' => false)
		);
	}

}
