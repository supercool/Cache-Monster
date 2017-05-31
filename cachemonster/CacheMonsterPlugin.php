<?php
namespace Craft;

/**
 * CacheMonster by Supercool
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonsterPlugin extends BasePlugin
{

	// Properties
	// =========================================================================


	// Public Methods
	// =========================================================================

	public function getName()
	{
		return Craft::t('CacheMonster');
	}

	public function getVersion()
	{
		return '2.0.1';
	}

	public function getDeveloper()
	{
		return 'Supercool';
	}

	public function getDeveloperUrl()
	{
		return 'http://plugins.supercooldesign.co.uk';
	}

	public function hasCpSection()
	{
		return true;
	}

	public function init()
	{

		// Import our non-autoloaded classes
		Craft::import('plugins.cachemonster.services.ICacheMonster_External');
		Craft::import('plugins.cachemonster.services.BaseCacheMonster_ExternalService');

		// TODO: move all the event listeners somewhere else - cluttering up the place

		/**
		 * Here we are making sure to add the element ids and element criteria to
		 * our db tables in between the start and end of our cache tags.
		 *
		 * The only way we can do this for the element ids at present is to listen
		 * to the global element population events - this is not ideal as it means
		 * our replacement cache service gets fired up each time this happens,
		 * rather than just when a template gets rendered on the front end.
		 */

		// Raised after Craft has built out an elements query, enabling plugins to modify the query.
		craft()->on('elements.onBuildElementsQuery', function(Event $event)
		{
			$criteria = $event->params['criteria'];
			craft()->cacheMonster_templateCache->includeCriteriaInTemplateCaches($criteria);
		});

		// Raised from `BaseTemplate::getAttribute()`
		// TODO: requires core modification, see README
		craft()->on('templates.onGetAttribute', function(Event $event)
		{

			$object = $event->params['object'];

			if (is_object($object) && $object instanceof BaseElementModel)
			{
				$elementId = $object->id;
				if ($elementId)
				{
					craft()->cacheMonster_templateCache->includeElementInTemplateCaches($elementId);
				}
			}

		});


		/**
		 * Here we are listening to the global events for when an element gets
		 * updated so we can remove the correct caches.
		 */

		// Raised right before any elements are about to be deleted.
		craft()->on('elements.onBeforeDeleteElements', function(Event $event)
		{
			$elementIds = $event->params['elementIds'];
			if ($elementIds)
			{
				craft()->cacheMonster_templateCache->deleteCachesByElementId($elementIds);
			}
		});

		// Raised right before an element is saved.
		craft()->on('elements.onSaveElement', function(Event $event)
		{
			$element = $event->params['element'];
			if ($element) {
				craft()->cacheMonster_templateCache->deleteCachesByElement($element);
			}
		});

		// Raised after a batch element action has been performed.
		craft()->on('elements.onPerformAction', function(Event $event)
		{
			$criteria = $event->params['criteria'];
			if ($criteria->id) {
				craft()->cacheMonster_templateCache->deleteCachesByElementId($criteria->id);
			}
		});

		// Raised when an element is moved within a structure.
		craft()->on('structures.onMoveElement', function(Event $event)
		{
			$element = $event->params['element'];
			if ($element) {
				craft()->cacheMonster_templateCache->deleteCachesByElement($element);
			}
		});

		// Raised right before a user is deleted.
		// NOTE: untested
		craft()->on('users.onBeforeDeleteUser', function(Event $event)
		{
			$user = $event->params['user'];
			$transferContentTo = $event->params['transferContentTo'];

			// Should we transfer the content to a new user?
			if ($user && $transferContentTo) {

				// Get the entry IDs that belong to this user
				$entryIds = craft()->db->createCommand()
					->select('id')
					->from('entries')
					->where(array('authorId' => $user->id))
					->queryColumn();

				// Delete the template caches for any entries authored by this user
				if ($entryIds) {
					craft()->cacheMonster_templateCache->deleteCachesByElementId($entryIds);
				}
			}
		});

		// Raised right before a user is deleted.
		// NOTE: untested
		craft()->on('i18n.onBeforeDeleteLocale', function(Event $event)
		{
			$localeId = $event->params['localeId'];
			$transferContentTo = $event->params['transferContentTo'];

			// Is the content being transferred?
			if ($transferContentTo) {

				// Get the section IDs that are enabled for this locale
				$sectionIds = craft()->db->createCommand()
					->select('sectionId')
					->from('sections_i18n')
					->where(array('locale' => $localeId))
					->queryColumn();

				// Figure out which ones are *only* enabled for this locale
				$soloSectionIds = array();

				foreach ($sectionIds as $sectionId)
				{
					$sectionLocales = craft()->sections->getSectionLocales($sectionId);

					if (count($sectionLocales) == 1 && $sectionLocales[0]->locale == $localeId)
					{
						$soloSectionIds[] = $sectionId;
					}
				}

				// Did we find any?
				if ($soloSectionIds)
				{

					// Get all of the entry IDs in those sections
					$entryIds = craft()->db->createCommand()
						->select('id')
						->from('entries')
						->where(array('in', 'sectionId', $soloSectionIds))
						->queryColumn();

					// Delete the template caches for any entries about to be moved to a different section
					if ($entryIds) {
						craft()->cacheMonster_templateCache->deleteCachesByElementId($entryIds);
					}

				}

			}

		});

	}

	public function onBeforeInstall()
	{
		$this->_createTemplateCacheTables();
	}

	public function onBeforeUninstall()
	{
		// drop our tables
		if (craft()->db->tableExists('cachemonster_templatecachecriteria'))
		{
			craft()->db->createCommand()->dropTable('cachemonster_templatecachecriteria');
		}
		if (craft()->db->tableExists('cachemonster_templatecacheelements'))
		{
			craft()->db->createCommand()->dropTable('cachemonster_templatecacheelements');
		}
		if (craft()->db->tableExists('cachemonster_templatecaches'))
		{
			craft()->db->createCommand()->dropTable('cachemonster_templatecaches');
		}
	}

	/**
	 * @return CacheMonsterTwigExtension
	 */
	public function addTwigExtension()
	{
		Craft::import('plugins.cachemonster.twigextensions.*');
		return new CacheMonsterTwigExtension();
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('cacheMonster/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Routes
	 */
	public function registerCpRoutes()
	{
		return array(
			'cachemonster' => array('action' => 'cacheMonster/index'),
		);
	}

	// Protected Methods
	// =========================================================================

	protected function defineSettings()
	{
		return array(
			//
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Creates the template cache tables.
	 *
	 * Forked from the Craft core at 2.6.2784
	 *
	 * @return null
	 */
	private function _createTemplateCacheTables()
	{
		CacheMonsterPlugin::log('Creating the cachemonster_templatecaches table.');

		craft()->db->createCommand()->createTable('cachemonster_templatecaches', array(
			'cacheKey'   => array('column' => ColumnType::Varchar, 'null' => false),
			'locale'     => array('column' => ColumnType::Locale, 'null' => false),
			'path'       => array('column' => ColumnType::Varchar),
			'expiryDate' => array('column' => ColumnType::DateTime, 'null' => false),
			'body'       => array('column' => ColumnType::MediumText, 'null' => false),
		), null, true, false);

		craft()->db->createCommand()->createIndex('cachemonster_templatecaches', 'expiryDate,cacheKey,locale,path');
		craft()->db->createCommand()->addForeignKey('cachemonster_templatecaches', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		CacheMonsterPlugin::log('Finished creating the cachemonster_templatecaches table.');
		CacheMonsterPlugin::log('Creating the cachemonster_templatecacheelements table.');

		craft()->db->createCommand()->createTable('cachemonster_templatecacheelements', array(
			'cacheId'   => array('column' => ColumnType::Int, 'null' => false),
			'elementId' => array('column' => ColumnType::Int, 'null' => false),
		), null, false, false);

		craft()->db->createCommand()->addForeignKey('cachemonster_templatecacheelements', 'cacheId', 'cachemonster_templatecaches', 'id', 'CASCADE', null);
		craft()->db->createCommand()->addForeignKey('cachemonster_templatecacheelements', 'elementId', 'elements', 'id', 'CASCADE', null);

		CacheMonsterPlugin::log('Finished creating the cachemonster_templatecacheelements table.');
		CacheMonsterPlugin::log('Creating the cachemonster_templatecachecriteria table.');

		craft()->db->createCommand()->createTable('cachemonster_templatecachecriteria', array(
			'cacheId'  => array('column' => ColumnType::Int, 'null' => false),
			'type'     => array('column' => ColumnType::Varchar, 'maxLength' => 150, 'null' => false),
			'criteria' => array('column' => ColumnType::Text, 'null' => false),
		), null, true, false);

		craft()->db->createCommand()->addForeignKey('cachemonster_templatecachecriteria', 'cacheId', 'cachemonster_templatecaches', 'id', 'CASCADE', null);
		craft()->db->createCommand()->createIndex('cachemonster_templatecachecriteria', 'type');

		CacheMonsterPlugin::log('Finished creating the cachemonster_templatecachecriteria table.');
	}
}
