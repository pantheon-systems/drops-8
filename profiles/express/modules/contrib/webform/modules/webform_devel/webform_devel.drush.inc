<?php

/**
 * @file
 * Webform module drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function webform_devel_drush_command() {
  $items = [];

  $items['webform-devel-reset'] = [
    'description' => 'Resets Webform user data and saved state for messages',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'webform-devel-reset' => 'Resets Webform user data and saved state for messages',
    ],
    'aliases' => ['wfdr', 'webform:devel:reset'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function webform_devel_drush_help($section) {
  switch ($section) {
    case 'drush:webform-reset':
      return dt('Resets Webform user data and saved state for messages');

    case 'meta:webform:title':
      return dt('Webform development commands');

    case 'meta:webform:summary':
      return dt('Developer specific commands for the Webform module.');
  }
}

/******************************************************************************/
// Reset.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 *
 * @see \Drupal\webform_devel\Commands\WebformDevelCommands::drush_webform_devel_reset()
 */
function drush_webform_devel_reset() {
  if (!drush_confirm(dt("Are you sure you want reset the Webform module's user data and saved state?"))) {
    return drush_user_abort();
  }

  drush_print(dt('Resetting message closed via State API…'));
  \Drupal::state()->delete('webform.element.message');

  drush_print(dt('Resetting message closed via User Data…'));
  \Drupal::service('user.data')->delete('webform', NULL, 'webform.element.message');
}
