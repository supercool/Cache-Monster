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
		return '0.1';
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
		 * Listen to the `elements.onBeforeSaveElement` event
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

			}

		});

		/**
		 * Listen to the `elements.onSaveElement` event
		 */
		// TODO: possibly ping pending tasks here
		// craft()->on('elements.onSaveElement', function(Event $event)
		// {
		//
		// });

	}


}
