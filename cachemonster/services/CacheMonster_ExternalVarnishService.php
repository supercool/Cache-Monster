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

class CacheMonster_ExternalVarnishService extends BaseApplicationComponent implements ICacheMonster_External
{

	/**
	 * Purges the given paths
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

		// Get the settings out of our config
		$settings = craft()->config->get('externalCachingServiceSettings', 'cacheMonster');

		// Loop the paths in this step
		foreach ($paths as $path)
		{
			// Strip 'site:' from the path if it exists
			// TODO: this could be in a base class
			$newPath = preg_replace('/site:/', '', $path, 1);

			// Make the base url
			$url = $settings['url'].$newPath;

			// Create the PURGE request
			$request = $client->createRequest('PURGE', $url);

			// Add it to the batch
			$batch->add($request);

			// TODO: add a request for each ip specified in the config here
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

}
