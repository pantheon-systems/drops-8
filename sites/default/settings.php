<?php

/**
 * Add the Drupal 8 CMI Directory Information directly in settings.php to make sure
 * Drupal knows all about that.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/2
 *
 */
if (isset($_SERVER['PRESSFLOW_SETTINGS'])) {
  if ($_SERVER['SCRIPT_NAME'] == '/core/install.php') {
    $config_directories = array(
      CONFIG_ACTIVE_DIRECTORY => 'sites/default/files',
      CONFIG_STAGING_DIRECTORY => 'sites/default/files',
    );
  }
  else {
    $config_directories = array(
      CONFIG_ACTIVE_DIRECTORY => 'sites/default/files/config/active',
      CONFIG_STAGING_DIRECTORY => 'sites/default/config',
    );
  }
}

/**
 * Override the $install_state variable to let Drupal know that the settings are verified
 * since they are being passed directly by the Pantheon.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/9
 *
 */
if (isset($_SERVER['PRESSFLOW_SETTINGS'])) {
  $GLOBALS['install_state']['settings_verified'] = TRUE;
}

/**
 * Allow Drupal 8 to Cleanly Redirect to Install.php For New Sites.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/3
 *
 */
if (isset($_SERVER['PRESSFLOW_SETTINGS']) && ($_SERVER['SCRIPT_NAME'] != '/core/install.php') && (!is_dir(__DIR__ . '/files/styles'))) {
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
if (isset($_SERVER['PRESSFLOW_SETTINGS'])) {
  $settings['hash_salt'] = $drupal_hash_salt;
}

/**
 * Prevents fatal error https://github.com/pantheon-systems/drops-8/issues/23
*/
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';


/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}
