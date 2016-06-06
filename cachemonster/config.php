<?php

return array(

	// Work exactly the same as the core, but specifically for CacheMonster
	'cacheElementQueries' => true,
	'enableTemplateCaching' => true,

	// External service settings - so far only Varnish supported so this is pretty specific to that
	'externalCachingService' => false, // Can be 'Varnish' or false
	'externalCachingServiceSettings' => array(
		'url' => CRAFT_SITE_URL
	),

	// Warming settings
	'enableCacheWarming' => true,

);
