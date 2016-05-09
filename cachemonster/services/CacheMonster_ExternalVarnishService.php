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
class CacheMonster_ExternalVarnishService extends BaseApplicationComponent
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
			// Make the url, stripping 'site:' from the path if it exists
			$newPath = preg_replace('/site:/', '', $path, 1);
			// TODO: this is where we could abstract to multiple Varnish servers
			$url = UrlHelper::getSiteUrl($newPath);

			// Create the PURGE request
			$request = $client->createRequest('PURGE', $url);

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
