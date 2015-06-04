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


	/**
	 * Gets the sitemap then caches and returns an array of the paths found in it
	 *
	 *  TODO: let user set sitemap location(s) in the cp, default to /sitemap.xml
	 *
	 * @method crawlSitemapForPaths
	 * @return array               an array of $paths
	 */
	public function crawlSitemapForPaths()
	{

		// Get any existing paths
		if ( ! $paths = craft()->cache->get('varnishPaths') )
		{
			craft()->cache->delete('varnishPaths');
			$paths = array();
		}

		// Get the (given) sitemap
		$client = new \Guzzle\Http\Client();
		$response = $client->get(UrlHelper::getSiteUrl('sitemap.xml'))->send();

		// Get the xml and add each url to the $paths array
		if ( $response->isSuccessful() )
		{
			$xml = $response->xml();

			foreach ($xml->url as $url)
			{
				$parts = parse_url((string)$url->loc);
				$paths[] = 'site:' . ltrim($parts['path'], '/');
			}
		}

		// Check $paths is unique
		$paths = array_unique($paths);

		// Stick it in the cache
		craft()->cache->set('varnishPaths', $paths);

		// Return the actual paths
		return $paths;

	}

}
