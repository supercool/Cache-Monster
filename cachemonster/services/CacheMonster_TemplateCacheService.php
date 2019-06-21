<?php
namespace Craft;

/**
 * Class CacheMonster_TemplateCacheService
 *
 * Forked from `TemplateCacheService` at 2.6.2784
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */
class CacheMonster_TemplateCacheService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * The table that template caches are stored in.
	 *
	 * @var string
	 */
	private static $_templateCachesTable = 'cachemonster_templatecaches';

	/**
	 * The table that template cache-element relations are stored in.
	 *
	 * @var string
	 */
	private static $_templateCacheElementsTable = 'cachemonster_templatecacheelements';

	/**
	 * The table that queries used within template caches are stored in.
	 *
	 * @var string
	 */
	private static $_templateCacheCriteriaTable = 'cachemonster_templatecachecriteria';

	/**
	 * The duration (in seconds) between the times when Craft will delete any expired template caches.
	 *
	 * @var int
	 */
	private static $_lastCleanupDateCacheDuration = 86400;

	/**
	 * The current request's path, as it will be stored in the templatecaches table.
	 *
	 * @var string
	 */
	private $_path;

	/**
	 * A list of queries (and their criteria attributes) that are active within the existing caches.
	 *
	 * @var array
	 */
	private $_cacheCriteria;

	/**
	 * A list of element IDs that are active within the existing caches.
	 *
	 * @var array
	 */
	private $_cacheElementIds;

	/**
	 * Whether expired caches have already been deleted in this request.
	 *
	 * @var bool
	 */
	private $_deletedExpiredCaches = false;

	/**
	 * Whether all caches have been deleted in this request.
	 *
	 * @var bool
	 */
	private $_deletedAllCaches = false;

	/**
	 * Whether all caches have been deleted, on a per-element type basis, in this request.
	 *
	 * @var bool
	 */
	private $_deletedCachesByElementType;

	// Public Methods
	// =========================================================================

	/**
	 * Returns a cached template by its key.
	 *
	 * @param string $key    The template cache key
	 * @param bool   $global Whether the cache would have been stored globally.
	 *
	 * @return string|null
	 */
	public function getTemplateCache($key, $global)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		// Take the opportunity to delete any expired caches
		$this->deleteExpiredCachesIfOverdue();

		$conditions = array('and', 'expiryDate > :now', 'cacheKey = :key', 'locale = :locale');

		$params = array(
			':now'    => DateTimeHelper::currentTimeForDb(),
			':key'    => $key,
			':locale' => craft()->language
		);

		if (!$global)
		{
			$conditions[] = 'path = :path';
			$params[':path'] = $this->_getPath();
		}

		$cachedBody = craft()->db->createCommand()
			->select('body')
			->from(static::$_templateCachesTable)
			->where($conditions, $params)
			->queryScalar();

		return ($cachedBody !== false ? $cachedBody : null);
	}

	/**
	 * Starts a new template cache.
	 *
	 * @param string $key The template cache key.
	 *
	 * @return null
	 */
	public function startTemplateCache($key)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		if (craft()->config->get('cacheElementQueries', 'cacheMonster'))
		{
			$this->_cacheCriteria[$key] = array();
		}

		$this->_cacheElementIds[$key] = array();
	}

	/**
	 * Includes an element criteria in any active caches.
	 *
	 * @param ElementCriteriaModel $criteria The element criteria.
	 *
	 * @return null
	 */
	public function includeCriteriaInTemplateCaches(ElementCriteriaModel $criteria)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		if (!empty($this->_cacheCriteria))
		{
			$criteriaHash = spl_object_hash($criteria);

			foreach (array_keys($this->_cacheCriteria) as $cacheKey)
			{
				$this->_cacheCriteria[$cacheKey][$criteriaHash] = $criteria;
			}
		}
	}

	/**
	 * Includes an element in any active caches.
	 *
	 * @param int $elementId The element ID.
	 *
	 * @return null
	 */
	public function includeElementInTemplateCaches($elementId)
	{

		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		if (!empty($this->_cacheElementIds))
		{
			foreach (array_keys($this->_cacheElementIds) as $cacheKey)
			{
				if (array_search($elementId, $this->_cacheElementIds[$cacheKey]) === false)
				{
					$this->_cacheElementIds[$cacheKey][] = $elementId;
				}
			}
		}
	}

	/**
	 * Ends a template cache.
	 *
	 * @param string      $key        The template cache key.
	 * @param bool        $global     Whether the cache should be stored globally.
	 * @param string|null $duration   How long the cache should be stored for.
	 * @param mixed|null  $expiration When the cache should expire.
	 * @param string      $body       The contents of the cache.
	 *
	 * @throws \Exception
	 * @return null
	 */
	public function endTemplateCache($key, $global, $duration, $expiration, $body)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		// If there are any transform generation URLs in the body, don't cache it.
		// stripslashes($body) in case the URL has been JS-encoded or something.
		// Can't use getResourceUrl() here because that will append ?d= or ?x= to the URL.
		if (strpos(stripslashes($body), UrlHelper::getSiteUrl(craft()->config->getResourceTrigger().'/transforms')))
		{
			return;
		}

		// Encode any 4-byte UTF-8 characters
		$body = StringHelper::encodeMb4($body);

		// Figure out the expiration date
		if ($duration)
		{
			$expiration = new DateTime($duration);
		}

		if (!$expiration)
		{
			$duration = craft()->config->getCacheDuration();

			if($duration <= 0)
			{
				$duration = 31536000; // 1 year
			}

			$duration += time();

			$expiration = new DateTime('@'.$duration);
		}

		// Save it
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			craft()->db->createCommand()->insert(static::$_templateCachesTable, array(
				'cacheKey'   => $key,
				'locale'     => craft()->language,
				'path'       => ($global ? null : $this->_getPath()),
				'expiryDate' => DateTimeHelper::formatTimeForDb($expiration),
				'body'       => $body
			), false);

			$cacheId = craft()->db->getLastInsertID();

			// Tag it with any element criteria that were output within the cache
			if (!empty($this->_cacheCriteria[$key]))
			{
				$values = array();

				foreach ($this->_cacheCriteria[$key] as $criteria)
				{
					$flattenedCriteria = $criteria->getAttributes(null, true);

					$values[] = array($cacheId, $criteria->getElementType()->getClassHandle(), JsonHelper::encode($flattenedCriteria));
				}

				craft()->db->createCommand()->insertAll(static::$_templateCacheCriteriaTable, array('cacheId', 'type', 'criteria'), $values, false);

				unset($this->_cacheCriteria[$key]);
			}

			// Tag it with any element IDs that were output within the cache
			if (!empty($this->_cacheElementIds[$key]))
			{
				$values = array();

				foreach ($this->_cacheElementIds[$key] as $elementId)
				{
					$values[] = array($cacheId, $elementId);
				}

				craft()->db->createCommand()->insertAll(static::$_templateCacheElementsTable, array('cacheId', 'elementId'), $values, false);

				unset($this->_cacheElementIds[$key]);
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}
			CacheMonsterPlugin::log('Couldn’t write to the db: '.$e->getMessage(), LogLevel::Error);
		}

	}


	// View Methods
	// =========================================================================

	/**
	 *
	 */
	public function getAllTemplateCaches()
	{
		$query = craft()->db->createCommand()
			->select('*')
			->limit('50')
			->from('cachemonster_templatecaches');

		$rows = $query->queryAll();

		return CacheMonster_TemplateCacheModel::populateModels($rows);
	}


	// Deletion Methods
	// =========================================================================

	/**
	 * Deletes a cache by its ID(s).
	 *
	 * @param int|array $cacheId The cache ID.
	 *
	 * @return bool
	 */
	public function deleteCacheById($cacheId)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (is_array($cacheId))
		{
			$condition = array('in', 'id', $cacheId);
			$params = array();
		}
		else
		{
			$condition = 'id = :id';
			$params = array(':id' => $cacheId);
		}

		// Allow stuff to happen with those caches before they get deleted
		$this->onBeforeDeleteTemplateCaches($cacheId);

		// Delete them
		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable, $condition, $params);
		return (bool) $affectedRows;
	}

	/**
	 * Deletes caches by a given element type.
	 *
	 * @param string $elementType The element type handle.
	 *
	 * @return bool
	 */
	public function deleteCachesByElementType($elementType)
	{
		if ($this->_deletedAllCaches || !empty($this->_deletedCachesByElementType[$elementType]) || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$this->_deletedCachesByElementType[$elementType] = true;

		$cacheIds = craft()->db->createCommand()
			->select('cacheId')
			->from(static::$_templateCacheCriteriaTable)
			->where(array('type' => $elementType))
			->queryColumn();

		if ($cacheIds)
		{
			// Allow stuff to happen with those caches before they get deleted
			$this->onBeforeDeleteTemplateCaches($cacheIds);

			// Delete them
			craft()->db->createCommand()->delete(static::$_templateCachesTable, array('in', 'id', $cacheIds));
		}

		return true;
	}

	/**
	 * Deletes caches that include a given element(s).
	 *
	 * @param BaseElementModel|BaseElementModel[] $elements The element(s) whose caches should be deleted.
	 *
	 * @return bool
	 */
	public function deleteCachesByElement($elements)
	{

		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (!$elements)
		{
			return false;
		}

		if (is_array($elements))
		{
			$firstElement = ArrayHelper::getFirstValue($elements);
		}
		else
		{
			$firstElement = $elements;
			$elements = array($elements);
		}

		$deleteQueryCaches = empty($this->_deletedCachesByElementType[$firstElement->getElementType()]);
		$elementIds = array();

		foreach ($elements as $element)
		{
			$elementIds[] = $element->id;
		}

		return $this->deleteCachesByElementId($elementIds, $deleteQueryCaches);
	}

	/**
	 * Deletes caches that include an a given element ID(s).
	 *
	 * @param int|array $elementId         The ID of the element(s) whose caches should be cleared.
	 * @param bool      $deleteQueryCaches Whether a DeleteStaleTemplateCaches task should be created, deleting any
	 *                                     query caches that may now involve this element, but hadn't previously.
	 *                                     (Defaults to `true`.)
	 *
	 * @return bool
	 */
	public function deleteCachesByElementId($elementId, $deleteQueryCaches = true)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (!$elementId)
		{
			return false;
		}
		
		if ($deleteQueryCaches && craft()->config->get('cacheElementQueries', 'cacheMonster'))
		{
			// If there are any pending CacheMonster_DeleteStaleTemplateCachesTask tasks, just append this element to it
			$task = craft()->tasks->getNextPendingTask('CacheMonster_DeleteStaleTemplateCaches');

			if ($task && is_array($task->settings))
			{
				$settings = $task->settings;
				
				if (!is_array($settings['elementId']))
				{
					$settings['elementId'] = array($settings['elementId']);
				}

				if (is_array($elementId))
				{
					$settings['elementId'] = array_merge($settings['elementId'], $elementId);
				}
				else
				{
					$settings['elementId'][] = $elementId;
				}

				// Make sure there aren't any duplicate element IDs
				$settings['elementId'] = array_unique($settings['elementId']);
				
				// Set the new settings and save the task
				$task->settings = $settings;
				craft()->tasks->saveTask($task, false);
			}
			else
			{
				
				craft()->tasks->createTask('CacheMonster_DeleteStaleTemplateCaches', null, array(
					'elementId' => $elementId
				));
			}
		}

		$query = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from(static::$_templateCacheElementsTable);

		if (is_array($elementId))
		{
			$query->where(array('in', 'elementId', $elementId));
		}
		else
		{
			$query->where('elementId = :elementId', array(':elementId' => $elementId));
		}

		$cacheIds = $query->queryColumn();
		
		if ($cacheIds)
		{
			return $this->deleteCacheById($cacheIds);
		}
		else
		{
			return false;
		}
	}

	private function clearExternalCache($elementId)
	{
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
    	$criteria->id = $elementId;
    	
    	$entry = $criteria->first();

		if ($entry) {
			$clearUrl = craft()->getSiteUrl().$entry->uri;

			if($clearUrl){
				// Ensure url scheme is correct
				$clearUrl = str_replace('http://', 'https://', $clearUrl);
				$paths = [];
				$paths[] = $clearUrl;
				// Ensure external cache gets cleared 
				craft()->tasks->createTask('CacheMonster_PurgeExternalCaches', null, array(
					'paths' => $paths
				));
			}
		} 
	}

	/**
	 * Deletes caches that include elements that match a given criteria.
	 *
	 * @param ElementCriteriaModel $criteria The criteria that should be used to find elements whose caches should be
	 *                                       deleted.
	 *
	 * @return bool
	 */
	public function deleteCachesByCriteria(ElementCriteriaModel $criteria)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$criteria->limit = null;
		$elementIds = $criteria->ids();

		return $this->deleteCachesByElementId($elementIds);
	}

	/**
	 * Deletes a cache by its key(s).
	 *
	 * @param int|array $key The cache key(s) to delete.
	 *
	 * @return bool
	 */
	public function deleteCachesByKey($key)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (is_array($key))
		{
			$condition = array('in', 'cacheKey', $key);
			$params = array();

			$keysToPurge = $key;
		}
		else
		{
			$condition = 'cacheKey = :cacheKey';
			$params = array(':cacheKey' => $key);

			$keysToPurge = array($key);
		}

		// Get the cache ids about to be deleted
		$query = craft()->db->createCommand()
			->select('id')
			->from('cachemonster_templatecaches')
			->where(array('in', 'cacheKey', $keysToPurge));

		$cacheIds = $query->queryColumn();

		// Allow stuff to happen with those caches before they get deleted
		$this->onBeforeDeleteTemplateCaches($cacheIds);

		// Delete them
		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable, $condition, $params);
		return (bool) $affectedRows;
	}

	/**
	 * Deletes any expired caches.
	 *
	 * @return bool
	 */
	public function deleteExpiredCaches()
	{
		if ($this->_deletedAllCaches || $this->_deletedExpiredCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		// Get the cache ids about to be deleted
		$query = craft()->db->createCommand()
			->select('id')
			->from('cachemonster_templatecaches')
			->where('expiryDate <= :now', array('now' => DateTimeHelper::currentTimeForDb()));

		$cacheIds = $query->queryColumn();

		// Allow stuff to happen with those caches before they get deleted
		$this->onBeforeDeleteTemplateCaches($cacheIds);

		// Delete them
		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable,
			'expiryDate <= :now',
			array('now' => DateTimeHelper::currentTimeForDb())
		);

		$this->_deletedExpiredCaches = true;

		return (bool) $affectedRows;
	}

	/**
	 * Deletes any expired caches if we haven't already done that within the past 24 hours.
	 *
	 * @return bool
	 */
	public function deleteExpiredCachesIfOverdue()
	{
		// Ignore if we've already done this once during the request
		if ($this->_deletedExpiredCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$lastCleanupDate = craft()->cache->get('cachemonster_lastTemplateCacheCleanupDate');

		if ($lastCleanupDate === false || DateTimeHelper::currentTimeStamp() - $lastCleanupDate > static::$_lastCleanupDateCacheDuration)
		{
			// Don't do it again for a while
			craft()->cache->set('cachemonster_lastTemplateCacheCleanupDate', DateTimeHelper::currentTimeStamp(), static::$_lastCleanupDateCacheDuration);

			return $this->deleteExpiredCaches();
		}
		else
		{
			$this->_deletedExpiredCaches = true;
			return false;
		}
	}

	/**
	 * Deletes all the template caches.
	 *
	 * @return bool
	 */
	public function deleteAllCaches()
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$this->_deletedAllCaches = true;

		// Get the cache ids about to be deleted
		$query = craft()->db->createCommand()
			->select('id')
			->from('cachemonster_templatecaches');

		$cacheIds = $query->queryColumn();

		// Remove all external caches
		$this->_purgeExternalCaches();
		
		// Delete them
		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable);
		return (bool) $affectedRows;
	}


	/**
	 * Triggers the purging and warming code before the tempalte caches get
	 * removed from the db
	 *
	 * @param int|array $cacheId The cache ID(s).
	 */
	public function onBeforeDeleteTemplateCaches($cacheIds)
	{
		// Pass the ids off to be purged externally
		$this->_purgeExternalCachesByCacheId($cacheIds);

		// Warm them too
		$this->_warmCachesByCacheId($cacheIds);
	}


	/**
	 * Returns the paths for a set of cache ids
	 *
	 * @param int|array $cacheId The cache ID.
	 *
	 * @return array
	 */
	public function getPathsByIds($cacheIds)
	{

		// TODO: could do a little internal caching here by saving the
		//       result to a variable keyed by a hash of the `$cacheIds`

		// Make $cacheIds an array if not
		if (!is_array($cacheIds))
		{
			$cacheIds = array($cacheIds);
		}

		// Get the paths that those caches related to
		$query = craft()->db->createCommand()
			->selectDistinct('path')
			->from('cachemonster_templatecaches')
			->where(array('in', 'id', $cacheIds));

		$paths = $query->queryColumn();

		return $paths;

	}


	/**
	 * Strips query params from paths
	 *
	 * @param  array $paths
	 * @return array
	 */
	public function stripQueryStringsFromPaths($paths)
	{

		foreach ($paths as $key => $path) {
			$paths[$key] = UrlHelper::stripQueryString($path);
		}

		return array_unique($paths);

	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the current request path, including a "site:" or "cp:" prefix.
	 *
	 * @return string
	 */
	private function _getPath()
	{
		if (!isset($this->_path))
		{
			if (craft()->request->isCpRequest())
			{
				$this->_path = 'cp:';
			}
			else
			{
				$this->_path = 'site:';
			}

			$this->_path .= craft()->request->getPath();

			if (($pageNum = craft()->request->getPageNum()) != 1)
			{
				$this->_path .= '/'.craft()->config->get('pageTrigger').$pageNum;
			}

			// Try and add the the query string, if we even should
			if (craft()->config->get('includeQueryString', 'cacheMonster') && $queryString = craft()->request->getQueryStringWithoutPath())
			{
				$queryString = trim($queryString, '&');

				if ($queryString)
				{
					$this->_path .= '?'.$queryString;
				}
			}
		}

		return $this->_path;
	}

	/**
	 * @return bool
	 */
	private function _isTemplateCachingEnabled()
	{
		if (craft()->config->get('enableTemplateCaching', 'cacheMonster'))
		{
			return true;
		}
	}


	/**
	 * @return bool
	 */
	private function _isExternalCachingEnabled()
	{
		if (craft()->config->get('externalCachingService', 'cacheMonster'))
		{
			return true;
		}
	}


	/**
	 * @return bool
	 */
	private function _isCacheWarmingEnabled()
	{
		if (craft()->config->get('enableCacheWarming', 'cacheMonster'))
		{
			return true;
		}
	}


	/**
	 * Purges external cache
	 *
	 * @return array|bool
	 */
	private function _purgeExternalCaches()
	{
		// Make sure external caching is enabled.
		if (!$this->_isExternalCachingEnabled())
		{
			return;
		}
		
		craft()->tasks->createTask('CacheMonster_FullPurgeExternalCaches');
	}

	/**
	 * Purges a cache for an external service by its ID(s).
	 *
	 * @param int|array $cacheId The cache ID.
	 *
	 * @return array|bool
	 */
	private function _purgeExternalCachesByCacheId($cacheId)
	{
		
		// Make sure external caching is enabled.
		if (!$this->_isExternalCachingEnabled())
		{
			return;
		}

		// Make $cacheId an array if not
		if (!is_array($cacheId))
		{
			$cacheId = array($cacheId);
		}

		// Get the paths
		$paths = $this->getPathsByIds($cacheId);
			
		if ($paths) {
			craft()->tasks->createTask('CacheMonster_PurgeExternalCaches', null, array(
				'paths' => !is_array($paths) ? array($paths) : $paths
			));
			
			return $paths;
		} else {
			return false;
		}

	}


	/**
	 * Warms a cache
	 *
	 * @param int|array $cacheId The cache ID.
	 *
	 * @return array|bool
	 */
	private function _warmCachesByCacheId($cacheId)
	{

		// Make sure external caching is enabled.
		if (!$this->_isCacheWarmingEnabled())
		{
			return;
		}

		// Make $cacheId an array if not
		if (!is_array($cacheId))
		{
			$cacheId = array($cacheId);
		}

		// Get the paths
		$paths = $this->getPathsByIds($cacheId);

		// Drop any paths with query strings if we need to
		if (craft()->config->get('includeQueryString', 'cacheMonster') && craft()->config->get('excludeQueryStringsWhenWarming', 'cacheMonster'))
		{
			$paths = $this->stripQueryStringsFromPaths($paths);
		}

		if ($paths) {

			// If there are any pending CacheMonster_WarmCaches tasks, just append this element to it
			$task = craft()->tasks->getNextPendingTask('CacheMonster_WarmCaches');

			if ($task && is_array($task->settings))
			{
				$settings = $task->settings;

				if (!is_array($settings['paths']))
				{
					$settings['paths'] = array($settings['paths']);
				}

				if (is_array($paths))
				{
					$settings['paths'] = array_merge($settings['paths'], $paths);
				}
				else
				{
					$settings['paths'][] = $paths;
				}

				// Make sure there aren't any duplicate paths
				$settings['paths'] = array_unique($settings['paths']);

				// Set the new settings and save the task
				$task->settings = $settings;
				craft()->tasks->saveTask($task, false);
			}
			else
			{
				craft()->tasks->createTask('CacheMonster_WarmCaches', null, array(
					'paths' => !is_array($paths) ? array($paths) : $paths
				));
			}

			return $paths;
		} else {
			return false;
		}

	}

}
