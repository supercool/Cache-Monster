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
	private $_plugin;

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
		return Craft::t('Getting the cached paths for processing');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
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

		// Get the actual paths out of the cache and dump it ready
		// for the next time it gets used
		if (!$this->_paths) {
			$this->_paths = craft()->cache->get("cacheMonsterPaths");
			// craft()->cache->delete("cacheMonsterPaths");
		}

		// Make the SubTasks
		if ($step == 0) {
			if ($this->_plugin->getSettings()['varnish'] && $this->_paths)
			{
				return $this->runSubTask('CacheMonster_Purge', null, array(
					'paths' => $this->_paths
				));
			} else {
				return true;
			}
		} elseif ($step == 1) {
			if ($this->_plugin->getSettings()['warm'] && $this->_paths)
			{
				return $this->runSubTask('CacheMonster_Warm', null, array(
					'paths' => $this->_paths
				));
			} else {
				return true;
			}
		}

	}

}
