<?php
namespace Craft;

/**
 * Class CacheMonster_ExternalVarnishService
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_ExternalVarnishService extends BaseCacheMonster_ExternalService
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
		$this->_settings = $settings['varnish'];
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
		$batch = \Guzzle\Batch\BatchBuilder::factory()
						->transferRequests(count($paths))
						->bufferExceptions()
						->build();

		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the Accept header
		$client->setDefaultOption('headers/Accept', '*/*');

		// Loop the paths in this step
		foreach ($paths as $path)
		{
			// Strip the prefixes from the path
			$path = $this->stripPrefixesFromPath($path);

			// Make the base url
			$url = $this->_settings['url'].$path;

			// Create the PURGE request
			$request = $client->createRequest('PURGE', $url);

			// Add it to the batch
			$batch->add($request);

			// TODO: add a request for each ip specified in the config here
			//       when supporting multiple ips
		}

		// Flush the queue and retrieve the flushed items
		$requests = $batch->flush();

		// Log any exceptions
		foreach ($batch->getExceptions() as $e)
		{
			CacheMonsterPlugin::log('An exception occurred: '.$e->getMessage(), LogLevel::Error);
		}

		// Clear any exceptions
		$batch->clearExceptions();

		// Just pretend it always worked
		return true;

	}

	public function fullPurge()
	{
		// Not implemented
		return true;
	}

}
