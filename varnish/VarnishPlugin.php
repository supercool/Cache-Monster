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

			$batch = \Guzzle\Batch\BatchBuilder::factory()
						->transferRequests(20)
						->build();

			// Make the client
			$client = new \Guzzle\Http\Client();

			// Set the Accept header
			$client->setDefaultOption('headers/Accept', '*/*');

			if ($paths)
			{

				foreach ($paths as $path)
				{

					// Make the url, stripping 'site:' from the path
					$newPath = preg_replace('/site:/', '', $path, 1);
					$url = UrlHelper::getSiteUrl($newPath);

					Craft::log('Adding URL: '.$url, LogLevel::Error, true);

					// Create the GET request
					$request = $client->get($url);

					// Add it to the batch
					$batch->add($request);

				}

				// Flush the queue and retrieve the flushed items
				$requests = $batch->flush();

				// FIXME: currently lots of 404 pages cause it to error out,
				//        in fact we shouldn’t warm error pages anyway!
				//
				//        AND to cap it all we should probably handle these exceptions properly

				echo "<pre>";
				print_r($requests);
				echo "</pre>";

				die();


				// $this->_makeTask('Varnish_Purge', $paths);
				// $this->_makeTask('Varnish_Warm', $paths);

			}

		});

	}


	/**
	 * [_makeTask description]
	 * @method _makeTask
	 * @param  [type]    $taskName [description]
	 * @param  [type]    $paths    [description]
	 * @return [type]              [description]
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
