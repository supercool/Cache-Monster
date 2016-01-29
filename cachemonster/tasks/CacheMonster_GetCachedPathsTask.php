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

class CacheMonster_GetCachedPathsTask extends BaseTask
{

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_elementIds;

	/**
	 * @var
	 */
	private $_plugin;


	// Public Methods
	// =========================================================================


	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Getting the cached paths for processing');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_elementIds = $this->getSettings()->elementId;
		$this->_plugin = craft()->plugins->getPlugin('cachemonster');

		return 2;
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

		// Get the actual paths out of the cache
		$paths = array();
		foreach ($this->_elementIds as $id) {
			$cache = craft()->cache->get("cacheMonsterPaths-{$id}");
			if ($cache) {
				$paths = array_merge($paths, $cache)
			}
		}

		// Make the SubTasks
		if ($step == 0) {
			if ($this->_plugin->getSettings()['varnish'])
			{
				return $this->runSubTask('CacheMonster_Purge', null, array(
					'paths' => $paths
				));
			} else {
				return true;
			}
		} elseif ($step == 1) {
			if ($this->_plugin->getSettings()['warm'])
			{
				return $this->runSubTask('CacheMonster_Warm', null, array(
					'paths' => $paths
				));
			} else {
				return true;
			}
		}

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
