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
		return '2.0.0';
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
		 * Here we are making sure to add the element ids and element criteria to
		 * our db tables in between the start and end of our cache tags.
		 *
		 * The only way we can do this for the element ids at present is to listen
		 * to the global element population events - this is not ideal as it means
		 * our replacement cache service gets fired up each time this happens,
		 * rather than just when a template gets rendered on the front end.
		 */

		// Raised when all of the element models have been populated from an element query.
		craft()->on('elements.onPopulateElements', function(Event $event)
		{
			$criteria = $event->params['criteria'];
			craft()->cacheMonster_templateCache->includeCriteriaInTemplateCaches($criteria);
		});

		// Raised when any element model is populated from its database result.
		craft()->on('elements.onPopulateElement', function(Event $event)
		{
			$element = $event->params['element'];

			if (is_object($element) && $element instanceof BaseElementModel)
			{
				$elementId = $element->id;
				if ($elementId)
				{
					craft()->cacheMonster_templateCache->includeElementInTemplateCaches($elementId);
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
		Craft::log('Creating the cachemonster_templatecaches table.');

		craft()->db->createCommand()->createTable('cachemonster_templatecaches', array(
			'cacheKey'   => array('column' => ColumnType::Varchar, 'null' => false),
			'locale'     => array('column' => ColumnType::Locale, 'null' => false),
			'path'       => array('column' => ColumnType::Varchar),
			'expiryDate' => array('column' => ColumnType::DateTime, 'null' => false),
			'body'       => array('column' => ColumnType::MediumText, 'null' => false),
		), null, true, false);

		craft()->db->createCommand()->createIndex('cachemonster_templatecaches', 'expiryDate,cacheKey,locale,path');
		craft()->db->createCommand()->addForeignKey('cachemonster_templatecaches', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		Craft::log('Finished creating the cachemonster_templatecaches table.');
		Craft::log('Creating the cachemonster_templatecacheelements table.');

		craft()->db->createCommand()->createTable('cachemonster_templatecacheelements', array(
			'cacheId'   => array('column' => ColumnType::Int, 'null' => false),
			'elementId' => array('column' => ColumnType::Int, 'null' => false),
		), null, false, false);

		craft()->db->createCommand()->addForeignKey('cachemonster_templatecacheelements', 'cacheId', 'cachemonster_templatecaches', 'id', 'CASCADE', null);
		craft()->db->createCommand()->addForeignKey('cachemonster_templatecacheelements', 'elementId', 'elements', 'id', 'CASCADE', null);

		Craft::log('Finished creating the cachemonster_templatecacheelements table.');
		Craft::log('Creating the cachemonster_templatecachecriteria table.');

		craft()->db->createCommand()->createTable('cachemonster_templatecachecriteria', array(
			'cacheId'  => array('column' => ColumnType::Int, 'null' => false),
			'type'     => array('column' => ColumnType::Varchar, 'maxLength' => 150, 'null' => false),
			'criteria' => array('column' => ColumnType::Text, 'null' => false),
		), null, true, false);

		craft()->db->createCommand()->addForeignKey('cachemonster_templatecachecriteria', 'cacheId', 'cachemonster_templatecaches', 'id', 'CASCADE', null);
		craft()->db->createCommand()->createIndex('cachemonster_templatecachecriteria', 'type');

		Craft::log('Finished creating the cachemonster_templatecachecriteria table.');
	}
}
