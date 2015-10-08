<?php

/**
 * @file
 * Pantheon configuration file.
 *
 * IMPORTANT NOTE: 
 * Do not modify this file. This file is maintained by Pantheon.
 *
 * Site-specific modifications belong in settings.php, not this file. This file
 * may change in future releases and modifications would cause conflicts when 
 * attempting to apply upstream updates.
 */

// Check to see if we are serving an installer page.
$is_installer_url = (strpos($_SERVER['SCRIPT_NAME'], '/core/install.php') === 0);

/**
 * Add the Drupal 8 CMI Directory Information directly in settings.php to make sure
 * Drupal knows all about that.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/2
 *
 * IMPORTANT SECURITY NOTE:  The configuration paths set up
 * below are secure when running your site on Pantheon.  If you
 * migrate your site to another environment on the public internet,
 * you should relocate these locations. See "After Installation"
 * at https://www.drupal.org/node/2431247
 *
 */
if ($is_installer_url) {
  $config_directories = array(
    CONFIG_STAGING_DIRECTORY => 'sites/default/files',
  );
}
else {
  $config_directories = array(
    CONFIG_STAGING_DIRECTORY => 'sites/default/config',
  );
}

/**
 * Override the $install_state variable to let Drupal know that the settings are verified
 * since they are being passed directly by the Pantheon.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/9
 *
 */
if (
  isset($_ENV['PANTHEON_ENVIRONMENT']) &&
  $is_installer_url &&
  (php_sapi_name() != "cli")
) {
  $GLOBALS['install_state']['settings_verified'] = TRUE;
}

/**
 * Allow Drupal 8 to Cleanly Redirect to Install.php For New Sites.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/3
 *
 * c.f. https://github.com/pantheon-systems/drops-8/pull/53
 *
 */
if (
  isset($_ENV['PANTHEON_ENVIRONMENT']) &&
  !$is_installer_url &&
  (!is_dir(__DIR__ . '/files/styles')) &&
  (empty($GLOBALS['install_state'])) &&
  (php_sapi_name() != "cli")
) {
  include_once __DIR__ . '/../../core/includes/install.core.inc';
  include_once __DIR__ . '/../../core/includes/install.inc';
  install_goto('core/install.php');
}

/**
 * Override the $databases variable to pass the correct Database credentials
 * directly from Pantheon to Drupal.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/8
 *
 */
if (isset($_SERVER['PRESSFLOW_SETTINGS'])) {
  $pressflow_settings = json_decode($_SERVER['PRESSFLOW_SETTINGS'], TRUE);
  foreach ($pressflow_settings as $key => $value) {
    // One level of depth should be enough for $conf and $database.
    if ($key == 'conf') {
      foreach($value as $conf_key => $conf_value) {
        $conf[$conf_key] = $conf_value;
      }
    }
    elseif ($key == 'databases') {
      // Protect default configuration but allow the specification of
      // additional databases. Also, allows fun things with 'prefix' if they
      // want to try multisite.
      if (!isset($databases) || !is_array($databases)) {
        $databases = array();
      }
      $databases = array_replace_recursive($databases, $value);
    }
    else {
      $$key = $value;
    }
  }
}

/**
 * Handle Hash Salt Value from Drupal
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/10
 *
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  $settings['hash_salt'] = $_ENV['DRUPAL_HASH_SALT'];
}


