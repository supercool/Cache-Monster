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
class CacheMonster_FullPurgeExternalCachesTask extends BaseTask
{

	// Properties
	// =========================================================================

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
		return Craft::t('Full purging external {service} caches', array('service' => $this->_service));
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		return 1;
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
		$this->_service = craft()->config->get('externalCachingService', 'cacheMonster');
		if (!$this->_service) {
			return true;
		}
		$service = 'cacheMonster_external'.$this->_service;

		return craft()->$service->fullPurge();
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
			
		);
	}

}
