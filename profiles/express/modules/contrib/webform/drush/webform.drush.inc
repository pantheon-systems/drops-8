<?php

// @codingStandardsIgnoreFile

/**
 * This is file was generated using Drush. DO NOT EDIT. 
 *
 * @see drush webform-generate-commands
 * @see \Drupal\webform\Commands\DrushCliServiceBase::generate_commands_drush8
 */

require_once __DIR__ . '/webform.drush.hooks.inc';

/**
 * Implements hook_drush_command().
 */
function webform_drush_command() {
  return array(
    'webform-export' => array(
      'description' => 'Exports webform submissions to a file.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 2,
      'arguments' => array(
        'webform' => 'The webform ID you want to export (required unless --entity-type and --entity-id are specified)',
      ),
      'options' => array(
        'exporter' => 'The type of export. (delimited, table, yaml, or json)',
        'delimiter' => 'Delimiter between columns (defaults to site-wide setting). This option may need to be wrapped in quotes. i.e. --delimiter="\t".',
        'multiple-delimiter' => 'Delimiter between an element with multiple values (defaults to site-wide setting).',
        'file-name' => 'File name used to export submission and uploaded filed. You may use tokens.',
        'archive-type' => 'Archive file type for submission file uploadeds and generated records. (tar or zip)',
        'header-format' => 'Set to "label" (default) or "key"',
        'options-item-format' => 'Set to "label" (default) or "key". Set to "key" to print select list values by their keys instead of labels.',
        'options-single-format' => 'Set to "separate" (default) or "compact" to determine how single select list values are exported.',
        'options-multiple-format' => 'Set to "separate" (default) or "compact" to determine how multiple select list values are exported.',
        'entity-reference-items' => 'Comma-separated list of entity reference items (id, title, and/or url) to be exported.',
        'excluded-columns' => 'Comma-separated list of component IDs or webform keys to exclude.',
        'uuid' => ' Use UUIDs for all entity references. (Only applies to CSV download)',
        'entity-type' => 'The entity type to which this submission was submitted from.',
        'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
        'range-type' => 'Range of submissions to export: "all", "latest", "serial", "sid", or "date".',
        'range-latest' => 'Integer specifying the latest X submissions will be downloaded. Used if "range-type" is "latest" or no other range options are provided.',
        'range-start' => 'The submission ID or start date at which to start exporting.',
        'range-end' => 'The submission ID or end date at which to end exporting.',
        'order' => 'The submission order "asc" (default) or "desc".',
        'state' => 'Submission state to be included: "completed", "draft" or "all" (default).',
        'sticky' => 'Flagged/starred submission status.',
        'files' => 'Download files: "1" or "0" (default). If set to 1, the exported CSV file and any submission file uploads will be download in a gzipped tar file.',
        'destination' => 'The full path and filename in which the CSV or archive should be stored. If omitted the CSV file or archive will be outputted to the command line.',
      ),
      'aliases' => array(
        'wfx',
        'webform:export',
      ),
    ),
    'webform-import' => array(
      'description' => 'Imports webform submissions from a CSV file.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 2,
      'arguments' => array(
        'webform' => 'The webform ID you want to import (required unless --entity-type and --entity-id are specified)',
        'import_uri' => 'The path or URI for the CSV file to be imported.',
      ),
      'options' => array(
        'skip_validation' => 'Skip form validation.',
        'treat_warnings_as_errors' => 'Treat all warnings as errors.',
        'entity-type' => 'The entity type to which this submission was submitted from.',
        'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
      ),
      'aliases' => array(
        'wfi',
        'webform:import',
      ),
    ),
    'webform-purge' => array(
      'description' => 'Purge webform submissions from the databases',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 2,
      'arguments' => array(
        'webform_id' => 'A webform machine name. If not provided, user may choose from a list of names.',
      ),
      'options' => array(
        'all' => 'Flush all submissions',
        'entity-type' => 'The entity type for webform submissions to be purged',
        'entity-id' => 'The ID of the entity for webform submissions to be purged',
      ),
      'examples' => array(
        'drush webform-purge' => 'Pick a webform and then purge its submissions.',
        'drush webform-purge contact' => "Delete 'Contact' webform submissions.",
        'drush webform-purge --all' => 'Purge all webform submissions.',
      ),
      'aliases' => array(
        'wfp',
        'webform:purge',
      ),
    ),
    'webform-tidy' => array(
      'description' => 'Tidy export webform configuration files',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'options' => array(
        'dependencies' => 'Add module dependencies to installed webform and options configuration entities.',
        'prefix' => 'Prefix for file names to be tidied. (Defaults to webform)',
      ),
      'arguments' => array(
        'target' => 'The module (config/install), config directory (sync), or path (/some/path) that needs its YAML configuration files tidied. (Defaults to webform)',
      ),
      'examples' => array(
        'drush webform-tidy webform' => "Tidies YAML configuration files in 'webform/config' for the Webform module",
      ),
      'aliases' => array(
        'wft',
        'webform:tidy',
      ),
    ),
    'webform-libraries-status' => array(
      'description' => 'Displays the status of third party libraries required by the Webform module.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-libraries-status' => 'Displays the status of third party libraries required by the Webform module.',
      ),
      'aliases' => array(
        'wfls',
        'webform:libraries:status',
      ),
    ),
    'webform-libraries-make' => array(
      'description' => 'Generates libraries YAML to be included in a drush.make.yml files.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-libraries-make' => 'Generates libraries YAML to be included in a drush.make.yml file.',
      ),
      'aliases' => array(
        'wflm',
        'webform:libraries:make',
      ),
    ),
    'webform-libraries-composer' => array(
      'description' => "Generates the Webform module's composer.json with libraries as repositories.",
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'options' => array(
        'disable-tls' => 'If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.',
      ),
      'examples' => array(
        'webform-libraries-composer' => "Generates the Webform module's composer.json with libraries as repositories.",
      ),
      'aliases' => array(
        'wflc',
        'webform:libraries:composer',
      ),
    ),
    'webform-libraries-download' => array(
      'description' => 'Download third party libraries required by the Webform module.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-libraries-download' => 'Download third party libraries required by the Webform module.',
      ),
      'aliases' => array(
        'wfld',
        'webform:libraries:download',
      ),
    ),
    'webform-libraries-remove' => array(
      'description' => 'Removes all downloaded third party libraries required by the Webform module.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-libraries-remove' => 'Removes all downloaded third party libraries required by the Webform module.',
      ),
      'aliases' => array(
        'wflr',
        'webform:libraries:remove',
      ),
    ),
    'webform-generate' => array(
      'description' => 'Create submissions in specified webform.',
      'arguments' => array(
        'webform_id' => 'Webform id into which new submissions will be inserted.',
        'num' => 'Number of submissions to insert. Defaults to 50.',
      ),
      'options' => array(
        'kill' => 'Delete all submissions in specified webform before generating.',
        'feedback' => 'An integer representing interval for insertion rate logging. Defaults to 1000',
        'entity-type' => 'The entity type to which this submission was submitted from.',
        'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
      ),
      'aliases' => array(
        'wfg',
        'webform:generate',
      ),
    ),
    'webform-repair' => array(
      'description' => 'Makes sure all Webform admin configuration and webform settings are up-to-date.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-repair' => 'Repairs admin configuration and webform settings are up-to-date.',
      ),
      'aliases' => array(
        'wfr',
        'webform:repair',
      ),
    ),
    'webform-remove-orphans' => array(
      'description' => "Removes orphaned submissions where the submission's webform was deleted.",
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-remove-orphans' => "Removes orphaned submissions where the submission's webform was deleted.",
      ),
      'aliases' => array(
        'wfro',
        'webform:remove:orphans',
      ),
    ),
    'webform-docs' => array(
      'description' => 'Generates HTML documentation.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'examples' => array(
        'webform-repair' => "Generates HTML documentation used by the Webform module's documentation pages.",
      ),
      'aliases' => array(
        'wfd',
        'webform:docs',
      ),
    ),
    'webform-composer-update' => array(
      'description' => "Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.",
      'core' => array(
        '8+',
      ),
      'bootstrap' => 1,
      'options' => array(
        'disable-tls' => 'If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.',
      ),
      'examples' => array(
        'webform-composer-update' => "Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.",
      ),
      'aliases' => array(
        'wfcu',
        'webform:composer:update',
      ),
    ),
    'webform-generate-commands' => array(
      'description' => 'Generate Drush commands from webform.drush.inc for Drush 8.x to WebformCommands for Drush 9.x.',
      'core' => array(
        '8+',
      ),
      'bootstrap' => 2,
      'examples' => array(
        'drush webform-generate-commands' => 'Generate Drush commands from webform.drush.inc for Drush 8.x to WebformCommands for Drush 9.x.',
      ),
      'aliases' => array(
        'wfgc',
        'webform:generate:commands',
      ),
    ),
  );
}

/******************************************************************************/
// drush webform-export. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_export_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_export_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_export() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_export'], func_get_args());
}

/******************************************************************************/
// drush webform-import. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_import_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_import_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_import() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_import'], func_get_args());
}

/******************************************************************************/
// drush webform-purge. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_purge_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_purge_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_purge() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_purge'], func_get_args());
}

/******************************************************************************/
// drush webform-tidy. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_tidy_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_tidy_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_tidy() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_tidy'], func_get_args());
}

/******************************************************************************/
// drush webform-libraries-status. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_status() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_libraries_status'], func_get_args());
}

/******************************************************************************/
// drush webform-libraries-make. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_make() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_libraries_make'], func_get_args());
}

/******************************************************************************/
// drush webform-libraries-composer. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_composer() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_libraries_composer'], func_get_args());
}

/******************************************************************************/
// drush webform-libraries-download. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_download() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_libraries_download'], func_get_args());
}

/******************************************************************************/
// drush webform-libraries-remove. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_remove() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_libraries_remove'], func_get_args());
}

/******************************************************************************/
// drush webform-generate. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_generate_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_generate_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_generate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_generate'], func_get_args());
}

/******************************************************************************/
// drush webform-repair. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_repair() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_repair'], func_get_args());
}

/******************************************************************************/
// drush webform-remove-orphans. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_remove_orphans() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_remove_orphans'], func_get_args());
}

/******************************************************************************/
// drush webform-docs. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_docs_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_docs_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_docs() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_docs'], func_get_args());
}

/******************************************************************************/
// drush webform-composer-update. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_composer_update_validate() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_composer_update_validate'], func_get_args());
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_composer_update() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_composer_update'], func_get_args());
}

/******************************************************************************/
// drush webform-generate-commands. DO NOT EDIT.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_generate_commands() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), 'drush_webform_generate_commands'], func_get_args());
}

