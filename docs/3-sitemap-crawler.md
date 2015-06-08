---
title:  "Sitemap crawler"
---

# Sitemap crawler

CacheMonster includes a controller action called `crawlAndWarm` that does two things; first it clears the entire Craft cache and then it crawls your xml site-map for urls and performs one GET request on all of them. This is publicly accessible and can be accessed using a url of the form `http://example.com/actions/cacheMonster/crawlAndWarm`. See the [Craft docs](http://buildwithcraft.com/docs/plugins/controllers#linking-directly-to-controller-actions) for more information on how to use controller actions directly.

It is mostly useful when used with CRON. For example, I want to purge all of Varnish and then run this action every night at half 1 in the morning:

    30 1 * * * (/etc/init.d/varnish restart > /dev/null 2>&1; sleep 45; /usr/bin/curl --silent -H "X-Requested-With:XMLHttpRequest" http://example.com/actions/cacheMonster/crawlAndWarm)

I have included a 45s sleep in this example as often you want to make sure Varnish has finished restarting.

Currently the sitemap must live at `/sitemap.xml`, in the future I hope to add various settings to allow you to override this.

You can read more about where this idea came from in [this blog post](http://supercool.github.io/2015/06/08/making-craft-sing-with-varnish-and-nginx.html#keeping-things-quick).
