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
		return '0.3';
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

				$this->_makeTask('Varnish_Purge', $paths);
				$this->_makeTask('Varnish_Warm', $paths);

			}

		});

	}


	/**
	 * Regusters a Task with Craft, taking into account if there
	 * is already one pending
	 *
	 * @method _makeTask
	 * @param  string    $taskName   the name of the Task you want to register
	 * @param  array     $paths      an array of paths that should go in that Tasks settings
	 */
	private function _makeTask($taskName, $paths)
	{

		// If there are any pending tasks, just append the paths to it
		$task = craft()->tasks->getNextPendingTask($taskName);

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
			craft()->tasks->createTask($taskName, null, array(
				'paths' => $paths
			));
		}

	}

}
