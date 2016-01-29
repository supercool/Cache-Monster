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

class CacheMonsterService extends BaseApplicationComponent
{

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

		// This might be heavy, probably not but better safe than sorry
		craft()->config->maxPowerCaptain();

		$paths = array();

		// Get the (one day specified) sitemap
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

		// Return the actual paths
		return $paths;

	}

}
