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

class VarnishPlugin extends BasePlugin
{

	public function getName()
	{
		return Craft::t('Varnish');
	}

	public function getVersion()
	{
		return '0.7';
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

			$elementId = $event->params['element']->id;

			if ( $elementId )
			{

				// Get the paths we need
				$paths = craft()->varnish->getPathsToPurge($elementId);

				if ($paths)
				{

					// Check if there are any already stored in the cache
					$existingPaths = craft()->cache->get('varnishPaths');
					craft()->cache->delete('varnishPaths');

					if ( $existingPaths )
					{
						$paths = array_merge($existingPaths, $paths);
						$paths = array_unique($paths);
					}

					// Store them in the cache so we can get them after
					// the element has actually saved
					craft()->cache->set('varnishPaths', $paths);

				}

			}

		});


		/**
		 * After the element has saved run the purging and warming tasks
		 */
		craft()->on('elements.onSaveElement', function(Event $event)
		{

			// Get the paths out of the cache
			$paths = craft()->cache->get('varnishPaths');

			if ($paths)
			{

				craft()->varnish->makeTask('Varnish_Purge', $paths);
				craft()->varnish->makeTask('Varnish_Warm', $paths);

			}

		});

	}

}
