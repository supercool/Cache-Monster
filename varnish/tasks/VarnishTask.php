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

class VarnishTask extends BaseTask
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
		return Craft::t('Purging the Varnish cache');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		// Get the actual paths out of the settings
		$paths = $this->getSettings()->paths;

		// Make our internal paths array
		$this->_paths = array();

		// Split the $paths array into chunks of 20 - each step
		// will be a batch of 20 requests
		$num = ceil( count($paths) / 20 );
		for ($i=0; $i < $num; $i++)
		{
			$this->_paths[] = array_slice($paths, $i, 20);
		}

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

		// TODO: sort out the url to be completely dynamic, siteUrl will be fine but right
		//       now we're testing on a non standard port
		$baseurl = 'http://craft.craft.dev:8080/';

		// TODO: make a batch here
		foreach ($this->_paths[$step] as $path)
		{
			$client = new \Guzzle\Http\Client();
			$url = $baseurl . preg_replace('/site:/', '', $path, 1);
			$request = $client->createRequest('PURGE', $url);
			$response = $request->send();
		}

		// TODO: once batched work out how to handle the exceptions and log them all
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
			'paths'  => AttributeType::Mixed
		);
	}

	// Private Methods
	// =========================================================================

}
