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

	public function getName()
	{
		return Craft::t('CacheMonster');
	}

	public function getVersion()
	{
		return '0.8';
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
				$paths = craft()->cacheMonster->getPathsToPurge($elementId);

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

				// Remove this swiftly, as it might cause issues if its used again
				craft()->cache->delete('cacheMonsterPaths-'.$elementId);

				// Use those paths to purge and warm
				if ($paths)
				{

					craft()->cacheMonster->makeTask('CacheMonster_Purge', $paths);
					craft()->cacheMonster->makeTask('CacheMonster_Warm', $paths);

				}

			}

		});

	}

}
