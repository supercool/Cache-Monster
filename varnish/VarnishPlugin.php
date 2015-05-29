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
		craft()->on('elements.onBeforeSaveElement', function(Event $event) {

			$elementId = $event->params['element']->id;



			// // If there are any pending DeleteStaleTemplateCaches tasks, just append this element to it
			// $task = craft()->tasks->getNextPendingTask('PurgeVarnish');
			//
			// if ($task && is_array($task->settings))
			// {
			// 	$settings = $task->settings;
			//
			// 	if (!is_array($settings['elementId']))
			// 	{
			// 		$settings['elementId'] = array($settings['elementId']);
			// 	}
			//
			// 	if (is_array($elementId))
			// 	{
			// 		$settings['elementId'] = array_merge($settings['elementId'], $elementId);
			// 	}
			// 	else
			// 	{
			// 		$settings['elementId'][] = $elementId;
			// 	}
			//
			// 	// Make sure there aren't any duplicate element IDs
			// 	$settings['elementId'] = array_unique($settings['elementId']);
			//
			// 	// Set the new settings and save the task
			// 	$task->settings = $settings;
			// 	craft()->tasks->saveTask($task, false);
			// }
			// else
			// {
			// 	craft()->tasks->createTask('DeleteStaleTemplateCaches', null, array(
			// 		'elementId' => $elementId
			// 	));
			// }

			// Get the cache ids that relate to this element
			$query = craft()->db->createCommand()
				->selectDistinct('cacheId')
				->from('templatecacheelements')
				->where('elementId = :elementId', array(':elementId' => $elementId));

			$cacheIds = $query->queryColumn();

			if ($cacheIds)
			{

				// Get the paths that those caches related to
				$query = craft()->db->createCommand()
					->selectDistinct('path')
					->from('templatecaches')
					->where(array('in', 'id', $cacheIds));

				$paths = $query->queryColumn();


				// Loop each path, getting each one
				if ($paths)
				{
					$baseurl = 'http://craft.craft.dev:8080/';

					foreach ($paths as $path)
					{
						$client = new \Guzzle\Http\Client();
						$url = $baseurl . preg_replace('/site:/', '', $path, 1);
						$request = $client->createRequest('PURGE', $url);
						$response = $request->send();
					}

				}

			}

			return true;

			// die();

			// 1. Get all the urls that are affected by this element id from the template caches table
			// 2. Make a task with those urls that goes over each one doing the guzzle

			// Guzzle method
			// $url = 'http://craft.craft.dev:8080/';
			//
			// $client = new \Guzzle\Http\Client();
			//
			// $request = $client->createRequest('PURGE', $url);
			//
			// try
			// {
			// 	$response = $request->send();
			//
			// 	return true;
			//
			// }
			// catch (\Exception $e)
			// {
			//
			// 	Craft::log('Varnish cache failed to purge. Message: ' . $e->getMessage(), LogLevel::Error);
			// 	return $e;
			//
			// }

		});

	}


}
