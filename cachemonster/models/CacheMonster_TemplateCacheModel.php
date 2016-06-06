<?php
namespace Craft;

/**
 * The template cache model
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_TemplateCacheModel extends BaseModel
{

	// Properties
	// =========================================================================

	/**
	 * @var path
	 */
	private $_path;


	// Public Methods
	// =========================================================================

	public function __toString()
	{
		return Craft::t($this->getPath());
	}


	public function getPath($stripPrefix = true)
	{

		if ($this->_path) {
			return $this->_path;
		}

		$this->_path = $this->path;

		if ($stripPrefix) {
			$this->_path = preg_replace('/site:/', '', $this->_path, 1);
			$this->_path = preg_replace('/cp:/', '', $this->_path, 1);
		}

		$this->_path = '/'.$this->_path;

		return $this->_path;

	}


	// Protected Methods
	// =========================================================================

	/**
	 * Defines this model's attributes.
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'         => AttributeType::Number,
			'cacheKey'   => AttributeType::String,
			'locale'     => AttributeType::Locale,
			'path'       => AttributeType::String,
			'expiryDate' => AttributeType::DateTime,
			'body'       => AttributeType::String,
		);
	}

}
