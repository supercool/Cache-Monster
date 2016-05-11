# CacheMonster

Caching on steroids for Craft CMS.


## New roadmap

1. [x] Fork the existing {% cache %} tag.

2. [x] Build an interface for integrating with external caching solutions such as Varnish, CloudFlare, AWS ElastiCache, Fastly etc. Start with just Varnish.

3. [ ] Add the cp settings page tool for purging our db caches, and separately the external ones

4. [ ] Cache warming - keep all the drivers warm! When something has been purged for whatever reason, re-make it in the internal and (optionally) external caches.

5. [ ] Provide a CRON companion to the warming so that when other factors are in play other than a manual purge due to a save or delete operation things can still be kept warm - could operate as a separate service entirely as it could be a bit heavy for the main web server to be running. This tool could also purge on a schedule too perhaps.

6. [ ] Integrate with other cache drivers aside from the db (will still need to use the db to store the data as a first port of call, just then serve it from the other options if it exists) for example: flat file (whole pages only), memcache, redis etc.

7. [ ] When it purges or warms the cache, raise an event before and after so other interested parties can get involved.

8. [ ] Add flags / tags ala https://github.com/mmikkel/CacheFlag-Craft

9. [ ] Provide a view in the cp for manually purging or warming caches
