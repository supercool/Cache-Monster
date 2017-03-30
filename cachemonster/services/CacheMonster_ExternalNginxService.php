<?php
namespace Craft;

/**
 * Class CacheMonster_ExternalNginxService
 *
 * @package   CacheMonster
 * @author    Naveed Ziarab
 * @copyright Copyright (c) 2017, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_ExternalNginxService extends BaseCacheMonster_ExternalService
{

	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_settings;


	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		// Get the settings out of our config
		$settings = craft()->config->get('externalCachingServiceSettings', 'cacheMonster');
		$this->_settings = $settings['nginx'];
	}

	/**
	 * @inheritDoc ICacheMonster_External::purgePaths()
	 *
	 * @param array $paths An array of paths to purge
	 *
	 * @return bool
	 */
	public function purgePaths($paths)
	{
		// Set up the batch
		// TODO: when supporting multiple ip addresses, multiply the path count by
		//       the number of ips listed

		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the Accept header
		$client->setDefaultOption('headers/Accept', '*/*');

		// Get Path only single url is passed by Task
		$path = $this->stripPrefixesFromPath($paths);

		// Make the base url
		$url = $this->_settings['url'].$path;

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