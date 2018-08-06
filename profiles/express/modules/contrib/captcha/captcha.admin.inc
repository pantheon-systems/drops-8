<?php

/**
 * @file
 * Functionality and helper functions for CAPTCHA administration.
 */

/**
 * Return an array with the available CAPTCHA types.
 *
 * For use as options array for a select form elements.
 *
 * @param bool $add_special_options
 *   If true: also add the 'default' option.
 *
 * @return array
 *   An associative array mapping "$module/$type" to
 *   "$type (from module $module)" with $module the module name
 *   implementing the CAPTCHA and $type the name of the CAPTCHA type.
 */
function _captcha_available_challenge_types($add_special_options = TRUE) {
  $captcha_types = [];
  if ($add_special_options) {
    $captcha_types['default'] = t('Default challenge type');
  }

  // We do our own version of Drupal's module_invoke_all() here because
  // we want to build an array with custom keys and values.
  foreach (\Drupal::moduleHandler()->getImplementations('captcha') as $module) {
    $result = call_user_func_array($module . '_captcha', ['list']);
    if (is_array($result)) {
      foreach ($result as $type) {
        $captcha_types["$module/$type"] = t('@type (from module @module)', [
          '@type' => $type,
          '@module' => $module,
        ]);
      }
    }
  }
  return $captcha_types;
}

/**
 * Helper function for generating an example challenge.
 */
function _captcha_generate_example_challenge($module, $type) {
  return [
    '#type' => 'captcha',
    '#captcha_type' => $module . '/' . $type,
    '#captcha_admin_mode' => TRUE,
  ];
}
