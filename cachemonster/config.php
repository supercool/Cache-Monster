<?php

return array(

	// Work exactly the same as the core, but specifically for CacheMonster
	'cacheElementQueries' => true,
	'enableTemplateCaching' => true,

	// Template caches options
	'includeQueryString' => true, // Set to false to stop including the query string in the stored path

	// External service settings - so far only Varnish and CloudFlare supported
	'externalCachingService' => false, // Varnish, Nginx, CloudFlare, or false
	'externalCachingServiceSettings' => array(

		// Varnish
		'varnish' => array(
			'url' => CRAFT_SITE_URL,
			'header' => null // if we need to send a host header to clear varnish behind cloudflare, in which case the url should be the varnish server url
		),

		// Nginx FastCGI
		'nginx' => array(
			'url' => CRAFT_SITE_URL,
		),

		// CloudFlare
		'cloudflare' => array(
			'authEmail' => null,
			'authKey' => null,
			'zoneId' => null
		),

	),

	// Warming settings
	'enableCacheWarming' => true,
	'cacheWarmingUrl' => CRAFT_SITE_URL,
	'excludeQueryStringsWhenWarming' => true, // By default CacheMonster won’t warm urls with query strings

);
