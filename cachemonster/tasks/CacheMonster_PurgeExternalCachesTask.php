<?php
namespace Craft;

/**
 * Purges external caches
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */
class CacheMonster_PurgeExternalCachesTask extends BaseTask
{

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_paths;

	/**
	 * @var
	 */
	private $_service;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		// TODO: work out the service from the plugin settings
		$this->_service = 'Varnish';
		return Craft::t('Purging external {service} caches', array('service' => $this->_service));
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		// Get the actual paths out of the settings
		$this->_paths = $this->getSettings()->paths;

		// For Varnish we know we can batch - so lets
		if ($this->_service == 'Varnish') {
			$this->_paths = array_chunk($this->_paths, 20);
		}

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
		$service = 'cacheMonster_external'.$this->_service;
		return craft()->$service->purgePaths($this->_paths[$step]);
	}

}
