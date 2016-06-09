<?php
namespace Craft;

/**
 * Class CacheMonster_WarmingService
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_WarmingService extends BaseApplicationComponent
{

	/**
	 * Warms the given path
	 *
	 * @param string $path An path to warm
	 *
	 * @return bool
	 */
	public function warmPath($path)
	{

		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the Accept header
		$client->setDefaultOption('headers/Accept', '*/*');

		// Strip the prefixes from the path
		$path = preg_replace('/site:/', '', $path, 1);
		$path = preg_replace('/cp:/', '', $path, 1);

		// Make the base url
		$cacheWarmingUrl = craft()->config->get('cacheWarmingUrl', 'cacheMonster');
		$url = $cacheWarmingUrl.$path;

		// Make a GET
		$request = $client->get($url);

		// Send it
		try
		{
			$response = $request->send();
		}
		catch (\Exception $e)
		{
			CacheMonsterPlugin::log('An exception occurred: '.$e->getMessage(), LogLevel::Error);
		}

		// Just pretend it always worked
		return true;

	}

}
