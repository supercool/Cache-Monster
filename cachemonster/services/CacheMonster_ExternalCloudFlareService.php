<?php
namespace Craft;

/**
 * Class CacheMonster_ExternalCloudFlareService
 *
 * @package   CacheMonster
 * @author    Josh Angell, Solomon Hawk
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

class CacheMonster_ExternalCloudFlareService extends BaseApplicationComponent implements ICacheMonster_External
{
	/**
	 * @var string
	 */
	private static $_cloudFlareApiBaseUrl = 'https://api.cloudflare.com/client/v4/zones/';

	/**
	 * Purges the given paths
	 *
	 * @param array $paths An array of paths to purge
	 *
	 * @return bool
	 */
	public function purgePaths($paths=array())
	{
		if (!$this->_hasValidSettings())
		{
			throw new Exception(Craft::t('Could not validate the CloudFlare cache settings! Please ensure you have entered an "authEmail", "authKey" and "zoneId".'));
		}

		$settings = craft()->config->get('externalCachingServiceSettings', 'cacheMonster');

		// Normalize the paths returned from Craft's cache table
		// by removing "site:"" and appending the site url
		$preparedPaths = $this->_preparePaths($paths);

		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the relevant headers
		$client->setDefaultOption('headers', array(
			'X-Auth-Email' => $settings['authEmail'],
			'X-Auth-Key'   => $settings['authKey'],
			'Content-Type' => 'application/json'
		));

		try
		{
			// Issue a DELETE request to the purge endpoint passing
			// along the list of files to be purged
			$url = $this->_purgeUrlForZone($settings['zoneId']);
			$body = json_encode(array('files' => $preparedPaths));
			$request = $client->delete($url);
			$request->setBody($body);
			$request->send();
		}
		catch (\Exception $e)
		{
			CacheMonsterPlugin::log('An exception occurred: '.$e->getMessage(), LogLevel::Error);
			return false;
		}

		// Just pretend it always worked
		return true;
	}

	// Private

	/**
	 * @param string $zoneId
	 */
	private function _purgeUrlForZone($zoneId)
	{
		return static::$_cloudFlareApiBaseUrl . $zoneId . '/purge_cache';
	}

	private function _hasValidSettings()
	{
		$settings = craft()->config->get('externalCachingServiceSettings', 'cacheMonster');

		return $settings['authEmail'] != "" && $settings['authKey'] != "" && $settings['zoneId'];
	}

	/**
	 * @param array $paths
	 */
	private function _preparePaths($paths=array())
	{
		$preparedPaths = array();

		foreach ($paths as $path)
		{
			$newPath = preg_replace('/site:/', '', $path, 1);
			$preparedPaths[] = UrlHelper::getSiteUrl($newPath);
		}

		return $preparedPaths;
	}
}
