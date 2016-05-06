# CacheMonster

Caching on steroids for Craft CMS.


## New roadmap

- Fork the existing {% cache %} tag

- Integrate with other cache drivers aside from the db (will still need to use the db to store the data as a first port of call, just then serve it from the other options if it exists) for example: flat file (whole pages only), memcache, redis etc.

- Integrate with external caching solutions on top of the internal drivers such as Varnish, CloudFlare, AWS ElastiCache, Fastly etc.

- Cache warming - keep all the drivers warm! When something has been purged for whatever reason, re-make it in the internal and (optionally) external caches.

- Provide a CRON companion to the warming so that when other factors are in play other than a manual purge due to a save or delete operation things can still be kept warm - could operate as a separate service entirely as it could be a bit heavy for the main web server to be running. This tool could also purge on a schedule too perhaps.

- When it purges or warms the cache, raise an event before and after so other interested parties can get involved.

- Add flags / tags ala https://github.com/mmikkel/CacheFlag-Craft
