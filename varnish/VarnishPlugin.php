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

			if ( $elementId )
			{
				craft()->varnish->purgeElementById($elementId);
			}

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


		});

	}


}
