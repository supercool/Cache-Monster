# CacheMonster

Caching on steroids for Craft CMS.


## Core modification

Currently for this plugin to work a couple of core modifications are needed.

Add the following to `craft/app/etc/templating/BaseTemplate.php` in the `getAttribute()` method before it returns:

```
craft()->templates->onGetAttribute(array(
	'object' => $object,
));
```

Then, anywhere in `craft/app/services/TemplatesService.php` class add:

```
public function onGetAttribute($params = array())
{
	$this->raiseEvent('onGetAttribute', new Event($this, $params));
}
```


## New roadmap

1. [x] Fork the existing {% cache %} tag.

2. [x] Build an interface for integrating with external caching solutions such as Varnish, CloudFlare, AWS ElastiCache, Fastly etc. Start with just Varnish.

3. [ ] Provide controller action for emptying the db caches, and also the external ones.

4. [x] Provide a basic view in the cp for manually deleting caches

5. [ ] Expand the view in the cp by providing a button for each option (delete from db, purge external, warm, all at once) - probably provide checklist of options and possibly warn user if just purging they will lose the stack of urls for warming.

6. [ ] Cache warming - keep all the drivers warm! When something has been purged for whatever reason, re-make it in the internal and (optionally) external caches. Probably work out a way of only warming things that don’t have query strings - this could just be an assumption or a configurable thing.

7. [ ] Look at improving the garbage collection (expired rows on the db table don’t get removed until the cleanup task runs).

8. [ ] Provide a CRON companion to the warming so that when other factors are in play other than a manual purge due to a save or delete operation things can still be kept warm - could operate as a separate service entirely (worker?) as it could be a bit heavy for the main web server to be running. This tool could also purge on a schedule too perhaps.

9. [ ] Integrate with other cache drivers aside from the db (will still need to use the db to store the data as a first port of call, just then serve it from the other options if it exists) for example: flat file (whole pages only), memcache, redis etc.

10. [ ] When it purges or warms the cache, raise an event before and after so other interested parties can get involved.

11. [ ] Add flags / tags ala https://github.com/mmikkel/CacheFlag-Craft

13. [ ] The external caching services should be defined as an array in the config - the order of which determines the order they are purged in. Their individual settings should be nested. This will allow multiple external servises to be used at once.

14. [ ] Add a Twig tag that can be used inside our `{% cachemonster %}` tags to send extra options to the criteria or expiry settings - initially to allow us to add expiry dates when inside a loop, and to send up the next pending element for a given loop using the post date so that the cache expiry is set to the same as the post date of the next element to go live in that loop.
