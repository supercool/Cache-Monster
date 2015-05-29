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
		$this->_paths = $this->getSettings()->paths;

		// TODO: Split the $paths array into chunks of 20 - each step
		//       will be a batch of 20 requests

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

		// TODO: make a batch here

		$baseurl = 'http://craft.craft.dev:8080/';

		foreach ($this->_paths as $path)
		{
			$client = new \Guzzle\Http\Client();
			$url = $baseurl . preg_replace('/site:/', '', $path, 1);
			$request = $client->createRequest('PURGE', $url);
			$response = $request->send();
		}

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
