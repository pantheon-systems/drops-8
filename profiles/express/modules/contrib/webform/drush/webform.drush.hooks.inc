<?php

/**
 * @file
 * Drush 8 hooks.
 */

/**
 * Implements hook_drush_sql_sync_sanitize().
 *
 * @see sql_drush_sql_sync_sanitize()
 * @see \Drush\Commands\core\SanitizeCommands::sanitizeSessions
 */
function webform_drush_sql_sync_sanitize($site) {
  $wrap_table_name = drush_get_option('db-prefix');

  $tables = array_intersect([
    'webform_submission',
    'webform_submission_data',
    'webform_submission_log',
  ], drush_sql_get_class()->listTables());

  $query = [];
  foreach ($tables as $table) {
    if ($wrap_table_name) {
      $table = "{{$table}}";
    }
    $query[] = 'TRUNCATE ' . $table . ';';
  }

  drush_sql_register_post_sync_op('webform_submission',
    dt('Truncate webform submission tables.'),
    implode(' ', $query));
}
