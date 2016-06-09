<?php

return array(

	// Work exactly the same as the core, but specifically for CacheMonster
	'cacheElementQueries' => true,
	'enableTemplateCaching' => true,

	// Template caches options
	'includeQueryString' => true, // Set to false to stop including the query string in the stored path

	// External service settings - so far only Varnish and CloudFlare supported
	'externalCachingService' => false, // Varnish, CloudFlare, or false

	'externalCachingServiceSettings' => array(
		'url' => CRAFT_SITE_URL,
		// the following external settings are specific to at least CloudFlare
		'authEmail' => null,
		'authKey' => null,
		'zoneId' => null
	),

	// Warming settings
	'enableCacheWarming' => true,
	'excludeQueryStringsWhenWarming' => true, // By default CacheMonster won’t warm urls with query strings

);
