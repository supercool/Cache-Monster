<?php

return array(

	// Work exactly the same as the core, but specifically for CacheMonster
	'cacheElementQueries' => true,
	'enableTemplateCaching' => true,

	// Template caches options
	'includeQueryString' => true, // Set to false to stop including the query string in the stored path

	// External service settings - so far only Varnish supported so this is pretty specific to that
	'externalCachingService' => false, // Can be 'Varnish' or false
	'externalCachingServiceSettings' => array(
		'url' => CRAFT_SITE_URL
	),

	// Warming settings
	'enableCacheWarming' => true,
	'excludeQueryStringsWhenWarming' => true, // By default CacheMonster won’t warm urls with query strings

);
