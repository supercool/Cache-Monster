<?php
namespace Craft;

/**
 * CacheMonster by Supercool
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2015, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_WarmTask extends BaseTask
{

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_paths;


	// Public Methods
	// =========================================================================


	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Warming all the caches');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$elementId = $this->getSettings()->elementId;

		// Get the actual paths out of the cache
		if (is_array($elementId)) {
			$paths = array();
			foreach ($elementId as $id) {
				$cache = craft()->cache->get("cacheMonsterPaths-{$id}");
				if ($cache) {
					$paths = array_merge($paths, $cache)
				}
			}
		} else {
			$paths = craft()->cache->get("cacheMonsterPaths-{$elementId}");
		}

		// Split the $paths array into chunks of 20 - each step
		// will be a batch of 20 requests
		$this->_paths = array_chunk($paths, 20);

		// Count our final chunked array
		return count($this->_paths);
	}

	/**
	 * @inheritDoc ITask::runStep()
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{

		// NOTE: Perhaps much of this should be moved into a service

		$batch = \Guzzle\Batch\BatchBuilder::factory()
						->transferRequests(20)
						->bufferExceptions()
						->build();

		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the Accept header
		$client->setDefaultOption('headers/Accept', '*/*');

		// Loop the paths in this step
		foreach ($this->_paths[$step] as $path)
		{

			// Make the url, stripping 'site:' from the path if it exists
			$newPath = preg_replace('/site:/', '', $path, 1);
			$url = UrlHelper::getSiteUrl($newPath);

			// Create the GET request
			$request = $client->get($url);

			// Add it to the batch
			$batch->add($request);

		}

		// Flush the queue and retrieve the flushed items
		$requests = $batch->flush();

		// Log any exceptions
		foreach ($batch->getExceptions() as $e)
		{
			Craft::log('CacheMonster: an exception occurred: '.$e->getMessage(), LogLevel::Error);
		}

		// Clear any exceptions
		$batch->clearExceptions();

		return true;

	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'elementId' => AttributeType::Mixed
		);
	}

}
