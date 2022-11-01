<?php

/**
 * @file
 * Drush site-specific configuration file.
 *
 * Rename this file to `drushrc.php` and customize to suit
 * your site-specific needs for Drush configuration settings.
 *
 * See also the example.drushrc.php file in the Drush 'examples'
 * directory for more configuration options.  Note that some
 * options must go in your local configuration file in
 * $HOME/.drush/drushrc.php.
 */

/**
 * Configuration filters:
 *
 * List extensions that should be ignored when exporting or
 * importing configuration.  Extensions in the 'skip-modules'
 * list will not be included in the exports in core.extension.yml
 * if enabled, and will not be removed from the exports in
 * core.extension.yml if present, even if disabled.  Similarly,
 * the 'skip-modules' list will prevent listed modules from being
 * enabled or disabled during configuration import, regardless
 * of whether or not it appears in core.extension.yml.
 *
 * This facility allows you to manually decide whether these
 * modules should be enabled or disabled on a per-environment basis.
 */
$command_specific['config-export']['skip-modules'] = array('devel');
$command_specific['config-import']['skip-modules'] = array('devel');

