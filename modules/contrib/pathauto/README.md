CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Benefits
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Notices
 * Faqs
 * Maintainers


INTRODUCTION
------------

The Pathauto module provides support functions for other modules to
automatically generate aliases based on appropriate criteria, with a
central settings path for site administrators.

Implementations are provided for core entity types: content, taxonomy terms,
and users (including blogs and forum pages).

Pathauto also provides a way to delete large numbers of aliases.This feature
is available at Administer > Configuration > Search and metadata > URL aliases > Delete aliases.


BENEFITS
--------

Besides making the page address more reflective of its content than
"node/138", it's important to know that modern search engines give
heavy weight to search terms which appear in a page's URL. By
automatically using keywords based directly on the page content in the URL,
relevant search engine hits for your page can be significantly enhanced.


REQUIREMENTS
------------

This module requires the following module:

 * Token - https://www.drupal.org/project/token
 * CTools - https://www.drupal.org/project/ctools


RECOMMENDED MODULES
-------------------

 * Redirect - https://www.drupal.org/project/redirect
 * Sub-pathauto (Sub-path URL Aliases) -
   https://www.drupal.org/project/subpathauto


INSTALLATION
------------

Install the module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
further information. Note that there are two dependencies.


CONFIGURATION
-------------

   1. Navigate to Administration > Extend and enable the Pathauto module.
   2. Configure the module at admin/config/search/path/patterns - add a new
      pattern by creating and clicking "Add Pathauto pattern".
   3. Fill out "Path pattern" with fx [node:title], choose which content
      types this applies to,give it a label (the name) and save it.
   4. When you save new content from now on, it should automatically be
      assigned an alternative URL.


NOTICES
-------

Pathauto just adds URL aliases to content, users, and taxonomy terms.
Because it's an alias, the standard Drupal URL (for example node/123 or
taxonomy/term/1) will still function as normal.  If you have external links
to your site pointing to standard Drupal URLs, or hardcoded links in a module,
template, content or menu which point to standard Drupal URLs it will bypass
the alias set by Pathauto.

There are reasons you might not want two URLs for the same content on your
site. If this applies to you, please note that you will need to update any
hard coded links in your content or blocks.

If you use the "system path" (i.e. node/10) for menu items and settings like
that, Drupal will replace it with the url alias.


FAQs
----

* URLs (not) Getting Replaced With Aliases?
   Please bear in mind that only URLs passed through Drupal's Drupal's URL and
   Link APIs will be replaced with their aliases during page output. If
   a module or your template contains hardcoded links, such as
   'href="node/$node->nid"', those won't get replaced with their corresponding
   aliases.

* Disabling Pathauto for a specific content type (or taxonomy)?
   When the pattern for a content type is left blank, the default pattern will
   be used. But if the default pattern is also blank, Pathauto will be disabled
   for that content type.


MAINTAINERS
-----------

The original module combined the functionality of Mike Ryan's autopath with
Tommy Sundstrom's path_automatic.

Significant enhancements were contributed by jdmquin @ www.bcdems.net.

Matt England added the tracker support (tracker support has been removed in
recent changes).

Other suggestions and patches contributed by the Drupal community.

Current maintainers:

 * Dave Reid - http://www.davereid.net
 * Sascha Grossenbacher - https://www.drupal.org/u/berdir
