<?php
namespace Craft;

/**
 * Class CacheMonster_ExternalVarnishService
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class BaseCacheMonster_ExternalService extends BaseApplicationComponent implements ICacheMonster_External
{

	// Public Methods
	// =========================================================================

	/**
	 * Purges the given paths
	 *
	 * @param array $paths An array of paths to purge
	 *
	 * @return bool
	 */
	public function purgePaths($paths)
	{
		return true;
	}

	/**
	 * Strips both the 'site:' and 'cp:' prefixes from a given path
	 *
	 * @param  stirng $path The path you want to strip the prefix from
	 * @return string       The un-prefixed path
	 */
	public function stripPrefixesFromPath($path)
	{
		$path = preg_replace('/site:/', '', $path, 1);
		$path = preg_replace('/cp:/', '', $path, 1);
		return $path;
	}

}
