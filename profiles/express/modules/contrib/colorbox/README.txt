CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Colorbox is a light-weight, customizable lightbox plugin for jQuery 1.4.3+.
This module allows for integration of Colorbox into Drupal.
The jQuery library is a part of Drupal since version 5+.

* jQuery - http://jquery.com/
* Colorbox - http://www.jacklmoore.com/colorbox/


FEATURES:
---------

The Colorbox module:

* Works as a Formatter in entities and in views.
* Excellent integration with core image field and image styles and the Insert
  module
* Choose between a default style and a number of other styles that are included.
* Style the Colorbox with a custom Colorbox style in your theme.
* Drush command, drush colorbox-plugin, to download and install the Colorbox
  plugin in "libraries/".

The Colorbox plugin:

* Compatible with: jQuery 1.3.2+ in Firefox, Safari, Chrome, Opera, Internet
  Explorer 7+
* Supports photos, grouping, slideshow, ajax, inline, and iframed content.
* Lightweight: 10KB of JavaScript (less than 5KBs gzipped).
* Appearance is controlled through CSS so it can be restyled.
* Can be extended with callbacks & event-hooks without altering the source
  files.
* Completely unobtrusive, options are set in the JS and require no changes to
  existing HTML.
* Preloads upcoming images in a photo group.
* Currently used on more than 2 million websites.
* Released under the MIT License.


REQUIREMENTS
------------

Just Colorbox plugin in "libraries".


INSTALLATION
------------

1. Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8

2. Download and unpack the Colorbox plugin in "libraries".
    Make sure the path to the plugin file becomes:
    "libraries/colorbox/jquery.colorbox-min.js"
   Link: https://github.com/jackmoore/colorbox/archive/master.zip
   Drush users can use the command "drush colorbox-plugin".

3. Go to "Administer" -> "Extend" and enable the Colorbox module.


CONFIGURATION
-------------

 * Go to "Configuration" -> "Media" -> "Colorbox" to find all the configuration
   options.

Add a custom Colorbox style to your theme:
----------------------------------------
The easiest way is to start with either the default style or one of the example
styles included in the Colorbox JS library download. Simply copy the entire
style folder to your theme and rename it to something logical like "mycolorbox".
Inside that folder are both a .css and .js file, rename both of those as well to
match your folder name: i.e. "colorbox_mycolorbox.css" and
"colorbox_mycolorbox.js"

Add entries in your theme's .info file for the Colorbox CSS/JS files:

stylesheets[all][] = mycolorbox/colorbox_mycolorbox.css
scripts[] = mycolorbox/colorbox_mycolorbox.js

Go to "Configuration" -> "Media" -> "Colorbox" and select "None" under
"Styles and Options". This will leave the styling of Colorbox up to your theme.
Make any CSS adjustments to your "colorbox_mycolorbox.css" file.


Drush:
------
A Drush command is provides for easy installation of the Colorbox plugin itself.

% drush colorbox-plugin

The command will download the plugin and unpack it in "libraries/".
It is possible to add another path as an option to the command, but not
recommended unless you know what you are doing.


MAINTAINERS
-----------

Current maintainers:

 * Fredrik Jonsson (frjo) - https://www.drupal.org/user/5546

Requires - Drupal 8
License - GPL (see LICENSE)
