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
	 * Wamrs the given paths
	 *
	 * @param array $paths An array of paths to warm
	 *
	 * @return bool
	 */
	public function warmPaths($paths)
	{

		// Set up the batch
		$batch = \Guzzle\Batch\BatchBuilder::factory()
						->transferRequests(count($paths))
						->bufferExceptions()
						->build();

		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the Accept header
		$client->setDefaultOption('headers/Accept', '*/*');

		// Loop the paths
		foreach ($paths as $path)
		{
			// Strip the prefixes from the path
			$path = preg_replace('/site:/', '', $path, 1);
			$path = preg_replace('/cp:/', '', $path, 1);

			// Make the base url
			$url = UrlHelper::getSiteUrl($path);

			// Create the GET request
			$request = $client->createRequest('GET', $url);

			// Add it to the batch
			$batch->add($request);
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
