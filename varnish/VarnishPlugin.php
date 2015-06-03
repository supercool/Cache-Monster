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
		return '0.2';
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

					$existingPaths = craft()->cache->get('varnishPaths');
					craft()->cache->delete('varnishPaths');

					if ( $existingPaths )
					{
						$paths = array_merge($existingPaths, $paths);
						$paths = array_unique($paths);
					}

					craft()->cache->set('varnishPaths', $paths);

				}

			}

		});


		/**
		 * After the element has saved run the actual purging task
		 */
		craft()->on('elements.onSaveElement', function(Event $event)
		{

			$paths = craft()->cache->get('varnishPaths');

			if ($paths)
			{

				// If there are any pending Varnish tasks, just append these path to it
				$task = craft()->tasks->getNextPendingTask('Varnish');

				if ($task && is_array($task->settings))
				{
					$settings = $task->settings;

					if (!is_array($settings['paths']))
					{
						$settings['paths'] = array($settings['paths']);
					}

					if (is_array($paths))
					{
						$settings['paths'] = array_merge($settings['paths'], $paths);
					}
					else
					{
						$settings['paths'][] = $paths;
					}

					// Make sure there aren't any duplicate paths
					$settings['paths'] = array_unique($settings['paths']);

					// Set the new settings and save the task
					$task->settings = $settings;
					craft()->tasks->saveTask($task, false);
				}
				else
				{
					craft()->tasks->createTask('Varnish', null, array(
						'paths' => $paths
					));
				}

			}

		});

	}


}
