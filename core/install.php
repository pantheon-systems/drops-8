<?php

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

// Change the directory to the Drupal root.
chdir('..');

/**
 * Global flag to indicate the site is in installation mode.
 *
 * The constant is defined using define() instead of const so that PHP
 * versions prior to 5.3 can display proper PHP requirements instead of causing
 * a fatal error.
 */
define('MAINTENANCE_MODE', 'install');

// Exit early if running an incompatible PHP version to avoid fatal errors.
// The minimum version is specified explicitly, as DRUPAL_MINIMUM_PHP is not
// yet available. It is defined in bootstrap.inc, but it is not possible to
// load that file yet as it would cause a fatal error on older versions of PHP.
if (version_compare(PHP_VERSION, '5.5.9') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.5.9. See the <a href="https://www.drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

// Start the installer.
$class_loader = require_once 'autoload.php';
require_once __DIR__ . '/includes/install.core.inc';
install_drupal($class_loader);
