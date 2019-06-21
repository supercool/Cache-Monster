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

class CacheMonster_ExternalCloudFlareService extends BaseCacheMonster_ExternalService
{

	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private static $_cloudFlareApiBaseUrl = 'https://api.cloudflare.com/client/v4/zones/';

	/**
	 * @var array
	 */
	private $_settings;


	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		// Get the settings out of our config
		$settings = craft()->config->get('externalCachingServiceSettings', 'cacheMonster');
		$this->_settings = $settings['cloudflare'];
	}

	/**
	 * @inheritDoc ICacheMonster_External::purgePaths()
	 *
	 * @param array $paths An array of paths to purge
	 *
	 * @return bool
	 */
	public function purgePaths($paths=array())
	{
		// Bail if the settings weren’t valid
		if (!$this->_hasValidSettings())
		{
			throw new Exception(Craft::t('Could not validate the CloudFlare cache settings! Please ensure you have entered an "authEmail", "authKey" and "zoneId".'));
		}
			
		// Have had issues with cache clears so

		// Normalize the paths
		$preparedPaths = $this->_preparePaths($paths);
		
		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the relevant headers
		$client->setDefaultOption('headers', array(
			'X-Auth-Email' => $this->_settings['authEmail'],
			'X-Auth-Key'   => $this->_settings['authKey'],
			'Content-Type' => 'application/json'
		));

		try
		{
			// Issue a POST request to the purge endpoint passing
			// along the list of files to be purged
			$url = $this->_purgeUrlForZone($this->_settings['zoneId']);
			// if need to purge all swap these two lines
			//$body = json_encode(array('purge_everything' => true));
			$body = json_encode(array('files' => $preparedPaths));
			$request = $client->post($url);
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

	/**
	 * @inheritDoc ICacheMonster_External::fullPurge()
	 *
	 * @return bool
	 */
	public function fullPurge()
	{
		// Bail if the settings weren’t valid
		if (!$this->_hasValidSettings())
		{
			throw new Exception(Craft::t('Could not validate the CloudFlare cache settings! Please ensure you have entered an "authEmail", "authKey" and "zoneId".'));
		}
	
		// Make the client
		$client = new \Guzzle\Http\Client();

		// Set the relevant headers
		$client->setDefaultOption('headers', array(
			'X-Auth-Email' => $this->_settings['authEmail'],
			'X-Auth-Key'   => $this->_settings['authKey'],
			'Content-Type' => 'application/json'
		));

		try
		{
			// Issue a POST request to the purge endpoint 
			$url = $this->_purgeUrlForZone($this->_settings['zoneId']);
			// if need to purge all swap these two lines
			$body = json_encode(array('purge_everything' => true));
			$request = $client->post($url);
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

	// Private Methods
	// =========================================================================

	/**
	 * Returns the purge url for a particular zone
	 *
	 * @param string $zoneId
	 * @return string
	 */
	private function _purgeUrlForZone($zoneId)
	{
		return static::$_cloudFlareApiBaseUrl . $zoneId . '/purge_cache';
	}

	/**
	 * Checks if the provided settings are valid
	 *
	 * @return boolean
	 */
	private function _hasValidSettings()
	{
		return $this->_settings['authEmail'] != "" && $this->_settings['authKey'] != "" && $this->_settings['zoneId'];
	}

	/**
	 * Normalizes the paths returned from Craft's cache table
	 * by removing any prefixs and appending the site url
	 *
	 * @param array $paths
	 * @return array
	 */
	private function _preparePaths($paths=array())
	{
		$preparedPaths = array();

		foreach ($paths as $path)
		{
			// Strip the prefixes from the path
			$path = $this->stripPrefixesFromPath($path);
			$preparedPaths[] = $clearUrl = str_replace('http://', 'https://', UrlHelper::getSiteUrl($path));
		}

		return $preparedPaths;
	}
}
