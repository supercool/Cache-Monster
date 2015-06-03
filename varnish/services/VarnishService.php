<?php
namespace Craft;

/**
 * Varnish by Supercool
 *
 * @package   Varnish
 * @author    Josh Angell
 * @copyright Copyright (c) 2015, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class VarnishService extends BaseApplicationComponent
{

	/**
	 * [purgeElementById description]
	 *
	 * @method getPathsToPurge
	 * @param  int           $elementId the elementId of the element we want purging
	 * @return array                    an array of paths to purge
	 */
	public function getPathsToPurge($elementId)
	{

		// Get the cache ids that relate to this element
		$query = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from('templatecacheelements')
			->where('elementId = :elementId', array(':elementId' => $elementId));

		$cacheIds = $query->queryColumn();

		if ($cacheIds)
		{

			// Get the paths that those caches related to
			$query = craft()->db->createCommand()
				->selectDistinct('path')
				->from('templatecaches')
				->where(array('in', 'id', $cacheIds));

			$paths = $query->queryColumn();

			// Return an array of them
			if ($paths)
			{

				if ( ! is_array($paths) )
				{
					$paths = array($paths);
				}

				return $paths;
			}
			else
			{
				return false;
			}

		}
		else
		{
			return false;
		}

	}

}
