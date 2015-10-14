<?php

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * This is the standard settings file provided by default
 * for Drupal sites running on the Pantheon platform.
 * This file will not be changed in updated versions, so
 * you may place your customizations here without causing
 * merge conflicts during upgrades.
 *
 * See default.settings.php for more configuration options.
 * You may copy the default.settings.php file over this
 * file if you wish.
 */

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Load local development override configuration, if available.
 *
 * Use settings.local.php to override variables on secondary (staging,
 * development, etc) installations of this site. Typically used to disable
 * caching, JavaScript/CSS compression, re-routing of outgoing emails, and
 * other things that should not happen on development and testing sites.
 *
 * Keep this code block at the end of this file to take full effect.
 */
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
