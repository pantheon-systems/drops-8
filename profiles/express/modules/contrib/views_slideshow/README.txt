INTRODUCTION
------------

  * Views Slideshow can be used to create a slideshow of any content (not just
    images) that can appear in a View. Powered by jQuery, it is heavily
    customizable: you may choose slideshow settings for each View you create.


REQUIREMENTS
------------

  * Views Slideshow 8.x-4.x requires Drupal 8 & the core views module enabled.

  * There is no upgrade path from views slideshow for Drupal 7.


INSTALLATION
------------
  * Install as you would normally install a contributed Drupal module. See the
  <a href='http://drupal.org/documentation/install/modules-themes/modules-8'>
  Drupal 8 instructions</a> if required in the Drupal documentation for further
  information. Note there are two modules included in this project; "Views
  Slideshow" & "Views Slideshow Cycle". In most cases you will need/want to
  enable both of them.

  * You will also need to download some JavaScript libraries. You can do this
  semi-automatically via drush using `drush dl-cycle-lib` or if preferred,
  manually from the sources.

  * Required libraries
    * https://malsup.github.io/jquery.cycle.all.js
    * http://cherne.net/brian/resources/jquery.hoverIntent.js
    * https://raw.githubusercontent.com/douglascrockford/JSON-js/master/json2.js

  * an example of code you could run in your Drupal root directory to download
    to the right place:

    ```
    mkdir -p libraries/jquery.cycle && cd $_ && wget https://malsup.github.io/jquery.cycle.all.js \
    && mkdir -p ../../libraries/jquery.hoverIntent && cd $_ && wget http://cherne.net/brian/resources/jquery.hoverIntent.js \
    && mkdir -p ../../libraries/json2 && cd $_ && wget https://raw.githubusercontent.com/douglascrockford/JSON-js/master/json2.js
    ```

CONFIGURATION
-------------

  * Configuration is on a per view/display basis. Select 'Slideshow' as the
    display format and then configure settings as desired under Format
    Settings.

  * See also:
        https://www.ostraining.com/blog/drupal/drupal-8-slideshows


MAINTAINERS
-----------

Current maintainers:

  * vbouchet (https://www.drupal.org/u/vbouchet) (Initial port & primary 8.x-4.x maintainer)

  * NickWilde (https://www.drupal.org/u/nickwilde) (Secondary 8.x-4.x maintainer)

  * aaron (https://www.drupal.org/u/aaron)

  * xiukun.zhou (https://www.drupal.org/u/xiukun.zhou)

  * wangqizhong (https://www.drupal.org/u/wangqizhong)
