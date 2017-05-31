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
		$this->_service = craft()->config->get('externalCachingService', 'cacheMonster');
		return Craft::t('Purging external {service} caches', array('service' => $this->_service));
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{

		$this->_service = craft()->config->get('externalCachingService', 'cacheMonster');

		if (!$this->_service) {
			return 0;
		}

		// Get the actual paths out of the settings
		$this->_paths = $this->getSettings()->paths;

		// Fix array keys as some of them were being skipped
		// which was causing php index undefined
		$this->_paths = array_values($this->_paths);

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

		if (!$this->_service) {
			return true;
		}

		$service = 'cacheMonster_external'.$this->_service;
		return craft()->$service->purgePaths($this->_paths[$step]);
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

}
