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

class CacheMonsterPlugin extends BasePlugin
{

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_settings;

	// Public Methods
	// =========================================================================

	public function getName()
	{
		return Craft::t('CacheMonster');
	}

	public function getVersion()
	{
		return '1.0.0';
	}

	public function getDeveloper()
	{
		return 'Supercool';
	}

	public function getDeveloperUrl()
	{
		return 'http://plugins.supercooldesign.co.uk';
	}

	public function init()
	{

		/**
		 * Get plugin settings
		 */
		$plugin = craft()->plugins->getPlugin('cachemonster');
		$this->_settings = $plugin->getSettings();

		/**
		 * Before we save, grab the paths that are going to be purged
		 * and create a Task that will save them to a cache
		 */
		craft()->on('elements.onBeforeSaveElement', function(Event $event)
		{

			// Don’t bother doing anything if neither warming or purging is needed
			if ($this->_settings['varnish'] || $this->_settings['warm'])
			{

				// Get the elementId
				$elementId = $event->params['element']->id;

				// NOTE: this works, but not if we run it in the Task!
				//
				// // What type of element(s) are we dealing with?
				// $elementType = craft()->elements->getElementTypeById($elementId);
				//
				//
				// $query = craft()->db->createCommand()
				// 	->from('templatecachecriteria');
				//
				// if (is_array($elementType))
				// {
				// 	$query->where(array('in', 'type', $elementType));
				// }
				// else
				// {
				// 	$query->where('type = :type', array(':type' => $elementType));
				// }
				//
				// // Figure out how many rows we're dealing with
				// $totalRows = $query->count('id');
				// Craft::dd($totalRows);

				// Make a Task that will get the paths and cache them
				$this->_makeTask('CacheMonster_CachePaths', $elementId);

			}

		});

		/**
		 *
		 */
		craft()->on('elements.onSaveElement', function(Event $event)
		{

			// Don’t bother doing anything if neither warming or purging is needed
			if ($this->_settings['varnish'] || $this->_settings['warm'])
			{

				// Get the elementId
				$elementId = $event->params['element']->id;

				// Make the manager Task that will fire the SubTasks
				craft()->tasks->createTask('CacheMonster_GetCachedPaths');

			}

		});

	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('cacheMonster/settings', array(
			'settings' => $this->getSettings()
		));
	}

	// Protected Methods
	// =========================================================================

	protected function defineSettings()
	{
		return array(
			'varnish' => array(AttributeType::Bool, 'default' => false),
			'warm' => array(AttributeType::Bool, 'default' => true)
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Registers a Task with Craft, taking into account if there
	 * is already one pending
	 *
	 * @method makeTask
	 * @param  string     $taskName   the name of the Task you want to register
	 * @param  int|array  $elementId
	 */
	private function _makeTask($taskName, $elementId)
	{

		$task = craft()->tasks->getNextPendingTask($taskName);

		if ($task && is_array($task->settings))
		{
			$settings = $task->settings;

			if (!is_array($settings['elementId']))
			{
				$settings['elementId'] = array($settings['elementId']);
			}

			if (is_array($elementId))
			{
				$settings['elementId'] = array_merge($settings['elementId'], $elementId);
			}
			else
			{
				$settings['elementId'][] = $elementId;
			}

			// Make sure there aren't any duplicate element IDs
			$settings['elementId'] = array_unique($settings['elementId']);

			// Set the new settings and save the task
			$task->settings = $settings;
			craft()->tasks->saveTask($task, false);
		}
		else
		{
			craft()->tasks->createTask($taskName, null, array(
				'elementId' => $elementId
			));
		}

	}

}
