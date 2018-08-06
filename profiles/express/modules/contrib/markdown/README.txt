Markdown filter Drupal module
=============================

Provides Markdown filter integration for Drupal text formats. The
Markdown syntax is designed to co-exist with HTML, so you can set up
input formats with both HTML and Markdown support. It is also meant to
be as human-readable as possible when left as "source".

There are many different Markdown implementation. Markdown filter can
at the moment use the following:

* PHP Markdown by Michel Fortin <https://michelf.ca/projects/php-markdown/>
* CommonMark <http://commonmark.org>


Installation:
------------

If you are comfortable with composer that is the best way to install both PHP
Markdown and CommonMark. They will then be autoloaded just like other parts of
Drupal 8.

The old way of installation in the libraries directory is only supported for PHP
Markdown. The libraries module is then needed to load the library.

1. Download and install the libraries module https://www.drupal.org/project/libraries.
2. Download the PHP Markdown library from
   https://github.com/michelf/php-markdown/archive/lib.zip, unpack it and place it
   in the "libraries" directory in Drupal root folder, if it doesn't exist you need
   to create it.

Make sure the path becomes "/libraries/php-markdown/Michelf/MarkdownExtra.inc.php".


Markdown editor:
---------------

If you are interested in a Markdown editor please check out
the Markdown editor for BUEditor module.

<http://drupal.org/project/markdowneditor>


Important note about running Markdown with other input filters:
--------------------------------------------------------------

Markdown may conflict with other input filters, depending on the order
in which filters are configured to apply. If using Markdown produces
unexpected markup when configured with other filters, experimenting with
the order of those filters will likely resolve the issue.

Filters that should be run before Markdown filter includes:

* Code Filter
* GeSHI filter for code syntax highlighting

Filters that should be run after Markdown filter includes:

* Typogrify

The "Limit allowed HTML tags" filter is a special case:

For best security, ensure that it is run after the Markdown filter and
that only markup you would like to allow via HTML and/or Markdown is
configured to be allowed.

If you on the other hand want to make sure that all converted Markdown
text is perserved, run it before the Markdown filter. Note that blockquoting
with Markdown doesn't work in this case since "Limit allowed HTML tags" filter
converts the ">" in to "&gt;".


Smartypants support:
-------------------

This module is a continuation of the Markdown with Smartypants module.
It only includes Markdown support and it is now suggested that you use
Typogrify module if you are interested in Smartypants support.

<http://drupal.org/project/typogrify>


Credits:
-------
Markdown created                     by John Gruber: <http://daringfireball.net>
Drupal filter originally             by Noah Mittman: <http://www.teradome.com/>
