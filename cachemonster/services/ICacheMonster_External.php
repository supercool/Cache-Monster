<?php
namespace Craft;

/**
 * Interface ICacheMonster_External
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

interface ICacheMonster_External
{

	// Public Methods
	// =========================================================================

	/**
	 * Purges the given paths
	 *
	 * @param array $paths
	 *
	 * @return bool
	 */
	public function purgePaths($paths);

}
