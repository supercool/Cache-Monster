---
title:  "How it works"
---

# How it works

This plugin listens to two events [elements.onBeforeSaveElement](http://buildwithcraft.com/docs/plugins/events-reference#elements-onBeforeSaveElement) and [elements.onSaveElement](http://buildwithcraft.com/docs/plugins/events-reference#elements-onSaveElement). Before the element is actually saved it grabs all the relevant template cache records that will be affected and stores their uri paths temporarily. Then, after the element has finished saving the plugin fire off a number of Tasks.

Firstly, if Varnish is enabled it will loop through each of the paths and send a PURGE request to them.

Secondly, it will loop all of those paths again and perform a standard GET request on them.

Thats it! Enjoy the speed.
