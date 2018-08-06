DESCRIPTION
-----------

This module uses the FitVids.js jQuery plugin for fluid/responsive video embeds (e.g. flash video in <iframe>s). You don't need it for pure HTML5 videos.

It supports YouTube, Vimeo, Blip.tv and Kickstarter by default, and you should be able to use it with other video providers.

It's useful if you are using a responsive theme (such as Bootstrap), and want the videos to scale to fit the available space.


REQUIREMENTS & INSTALLATION
---------------------------

This module uses the Libraries API. 

Before you install this module you'll need to download the FitVids.js plugin.

Download it from https://raw.github.com/davatron5000/FitVids.js/master/jquery.fitvids.js

Place the file in the /libraries/fitvids folder*, so the final path is /libraries/fitvids/jquery.fitvids.js

* If the /libraries folder does not exist, you'll need to create it. 


CONFIGURATION
-------------

# Video containers

You can usually use the defaults. It assumes that you'll want to apply it to all videos on the page. 

If your theme uses a different class or id, or you only want to target certain videos, you can specify that class/id in the video containers field. You can use any valid jQuery selector, e.g.,

~~~
.node
article
#my-video-container
~~~

You can specify as many containers as you want.


# Additional video providers

Not all video players will work with FitVids, but you can try it out by adding the domain (in the Custom iframe URLs field).

Use a new line for each URL. Don't add trailing slashes.
For example: "http://www.dailymotion.com" not "http://www.dailymotion.com/"


# Ignore these videos

If you have some videos (or iframes) that you don't want to be fluid/responsive, then enter their container class or id here. You can use any valid jQuery selector, e.g.,

~~~
.fixed-width-video
~~~
