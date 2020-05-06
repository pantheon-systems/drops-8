<?php
// @codingStandardsIgnoreFile

/**
 * This is file was generated using Drush. DO NOT EDIT. 
 *
 * @see drush webform-generate-commands
 * @see \Drupal\webform\Commands\DrushCliServiceBase::generate_commands_drush9
 */
namespace Drupal\webform\Commands;

use Consolidation\AnnotatedCommand\CommandData;

/**
 * Webform commands for Drush 9.x.
 */
class WebformCommands extends WebformCommandsBase {

  /****************************************************************************/
  // drush webform:export. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:export
   */
  public function drush_webform_export_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_export_validate'], $arguments);
  }

  /**
   * Exports webform submissions to a file.
   *
   * @command webform:export
   * @param $webform The webform ID you want to export (required unless --entity-type and --entity-id are specified)
   * @option exporter The type of export. (delimited, table, yaml, or json)
   * @option delimiter Delimiter between columns (defaults to site-wide setting). This option may need to be wrapped in quotes. i.e. --delimiter="\t".
   * @option multiple-delimiter Delimiter between an element with multiple values (defaults to site-wide setting).
   * @option file-name File name used to export submission and uploaded filed. You may use tokens.
   * @option archive-type Archive file type for submission file uploadeds and generated records. (tar or zip)
   * @option header-format Set to "label" (default) or "key"
   * @option options-item-format Set to "label" (default) or "key". Set to "key" to print select list values by their keys instead of labels.
   * @option options-single-format Set to "separate" (default) or "compact" to determine how single select list values are exported.
   * @option options-multiple-format Set to "separate" (default) or "compact" to determine how multiple select list values are exported.
   * @option entity-reference-items Comma-separated list of entity reference items (id, title, and/or url) to be exported.
   * @option excluded-columns Comma-separated list of component IDs or webform keys to exclude.
   * @option uuid  Use UUIDs for all entity references. (Only applies to CSV download)
   * @option entity-type The entity type to which this submission was submitted from.
   * @option entity-id The ID of the entity of which this webform submission was submitted from.
   * @option range-type Range of submissions to export: "all", "latest", "serial", "sid", or "date".
   * @option range-latest Integer specifying the latest X submissions will be downloaded. Used if "range-type" is "latest" or no other range options are provided.
   * @option range-start The submission ID or start date at which to start exporting.
   * @option range-end The submission ID or end date at which to end exporting.
   * @option order The submission order "asc" (default) or "desc".
   * @option state Submission state to be included: "completed", "draft" or "all" (default).
   * @option sticky Flagged/starred submission status.
   * @option files Download files: "1" or "0" (default). If set to 1, the exported CSV file and any submission file uploads will be download in a gzipped tar file.
   * @option destination The full path and filename in which the CSV or archive should be stored. If omitted the CSV file or archive will be outputted to the command line.
   * @aliases wfx,webform-export
   */
  public function drush_webform_export($webform = NULL, array $options = ['exporter' => NULL, 'delimiter' => NULL, 'multiple-delimiter' => NULL, 'file-name' => NULL, 'archive-type' => NULL, 'header-format' => NULL, 'options-item-format' => NULL, 'options-single-format' => NULL, 'options-multiple-format' => NULL, 'entity-reference-items' => NULL, 'excluded-columns' => NULL, 'uuid' => NULL, 'entity-type' => NULL, 'entity-id' => NULL, 'range-type' => NULL, 'range-latest' => NULL, 'range-start' => NULL, 'range-end' => NULL, 'order' => NULL, 'state' => NULL, 'sticky' => NULL, 'files' => NULL, 'destination' => NULL]) {
    $this->cliService->drush_webform_export($webform);
  }

  /****************************************************************************/
  // drush webform:import. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:import
   */
  public function drush_webform_import_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_import_validate'], $arguments);
  }

  /**
   * Imports webform submissions from a CSV file.
   *
   * @command webform:import
   * @param $webform The webform ID you want to import (required unless --entity-type and --entity-id are specified)
   * @param $import_uri The path or URI for the CSV file to be imported.
   * @option skip_validation Skip form validation.
   * @option treat_warnings_as_errors Treat all warnings as errors.
   * @option entity-type The entity type to which this submission was submitted from.
   * @option entity-id The ID of the entity of which this webform submission was submitted from.
   * @aliases wfi,webform-import
   */
  public function drush_webform_import($webform = NULL, $import_uri = NULL, array $options = ['skip_validation' => NULL, 'treat_warnings_as_errors' => NULL, 'entity-type' => NULL, 'entity-id' => NULL]) {
    $this->cliService->drush_webform_import($webform, $import_uri);
  }

  /****************************************************************************/
  // drush webform:purge. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:purge
   */
  public function drush_webform_purge_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_purge_validate'], $arguments);
  }

  /**
   * Purge webform submissions from the databases
   *
   * @command webform:purge
   * @param $webform_id A webform machine name. If not provided, user may choose from a list of names.
   * @option all Flush all submissions
   * @option entity-type The entity type for webform submissions to be purged
   * @option entity-id The ID of the entity for webform submissions to be purged
   * @usage drush webform:purge
   *   Pick a webform and then purge its submissions.
   * @usage drush webform:purge contact
   *   Delete 'Contact' webform submissions.
   * @usage drush webform:purge ::all
   *   Purge all webform submissions.
   * @aliases wfp,webform-purge
   */
  public function drush_webform_purge($webform_id = NULL, array $options = ['all' => FALSE, 'entity-type' => NULL, 'entity-id' => NULL]) {
    $this->cliService->drush_webform_purge($webform_id);
  }

  /****************************************************************************/
  // drush webform:tidy. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:tidy
   */
  public function drush_webform_tidy_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_tidy_validate'], $arguments);
  }

  /**
   * Tidy export webform configuration files
   *
   * @command webform:tidy
   * @param $target The module (config/install), config directory (sync), or path (/some/path) that needs its YAML configuration files tidied. (Defaults to webform)
   * @option dependencies Add module dependencies to installed webform and options configuration entities.
   * @option prefix Prefix for file names to be tidied. (Defaults to webform)
   * @usage drush webform:tidy webform
   *   Tidies YAML configuration files in 'webform/config' for the Webform module
   * @aliases wft,webform-tidy
   */
  public function drush_webform_tidy($target = NULL, array $options = ['dependencies' => FALSE, 'prefix' => NULL]) {
    $this->cliService->drush_webform_tidy($target);
  }

  /****************************************************************************/
  // drush webform:libraries:status. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Displays the status of third party libraries required by the Webform module.
   *
   * @command webform:libraries:status
   * @usage webform:libraries:status
   *   Displays the status of third party libraries required by the Webform module.
   * @aliases wfls,webform-libraries-status
   */
  public function drush_webform_libraries_status() {
    $this->cliService->drush_webform_libraries_status();
  }

  /****************************************************************************/
  // drush webform:libraries:make. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Generates libraries YAML to be included in a drush.make.yml files.
   *
   * @command webform:libraries:make
   * @usage webform:libraries:make
   *   Generates libraries YAML to be included in a drush.make.yml file.
   * @aliases wflm,webform-libraries-make
   */
  public function drush_webform_libraries_make() {
    $this->cliService->drush_webform_libraries_make();
  }

  /****************************************************************************/
  // drush webform:libraries:composer. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Generates the Webform module's composer.json with libraries as repositories.
   *
   * @command webform:libraries:composer
   * @option disable-tls If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.
   * @usage webform:libraries:composer
   *   Generates the Webform module's composer.json with libraries as repositories.
   * @aliases wflc,webform-libraries-composer
   */
  public function drush_webform_libraries_composer(array $options = ['disable-tls' => FALSE]) {
    $this->cliService->drush_webform_libraries_composer();
  }

  /****************************************************************************/
  // drush webform:libraries:download. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Download third party libraries required by the Webform module.
   *
   * @command webform:libraries:download
   * @usage webform:libraries:download
   *   Download third party libraries required by the Webform module.
   * @aliases wfld,webform-libraries-download
   */
  public function drush_webform_libraries_download() {
    $this->cliService->drush_webform_libraries_download();
  }

  /****************************************************************************/
  // drush webform:libraries:remove. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Removes all downloaded third party libraries required by the Webform module.
   *
   * @command webform:libraries:remove
   * @usage webform:libraries:remove
   *   Removes all downloaded third party libraries required by the Webform module.
   * @aliases wflr,webform-libraries-remove
   */
  public function drush_webform_libraries_remove() {
    $this->cliService->drush_webform_libraries_remove();
  }

  /****************************************************************************/
  // drush webform:generate. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:generate
   */
  public function drush_webform_generate_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_generate_validate'], $arguments);
  }

  /**
   * Create submissions in specified webform.
   *
   * @command webform:generate
   * @param $webform_id Webform id into which new submissions will be inserted.
   * @param $num Number of submissions to insert. Defaults to 50.
   * @option kill Delete all submissions in specified webform before generating.
   * @option feedback An integer representing interval for insertion rate logging. Defaults to 1000
   * @option entity-type The entity type to which this submission was submitted from.
   * @option entity-id The ID of the entity of which this webform submission was submitted from.
   * @aliases wfg,webform-generate
   */
  public function drush_webform_generate($webform_id = NULL, $num = NULL, array $options = ['kill' => FALSE, 'feedback' => NULL, 'entity-type' => NULL, 'entity-id' => NULL]) {
    $this->cliService->drush_webform_generate($webform_id, $num);
  }

  /****************************************************************************/
  // drush webform:repair. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Makes sure all Webform admin configuration and webform settings are up-to-date.
   *
   * @command webform:repair
   * @usage webform:repair
   *   Repairs admin configuration and webform settings are up-to-date.
   * @aliases wfr,webform-repair
   */
  public function drush_webform_repair() {
    $this->cliService->drush_webform_repair();
  }

  /****************************************************************************/
  // drush webform:remove:orphans. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Removes orphaned submissions where the submission's webform was deleted.
   *
   * @command webform:remove:orphans
   * @usage webform:remove:orphans
   *   Removes orphaned submissions where the submission's webform was deleted.
   * @aliases wfro,webform-remove-orphans
   */
  public function drush_webform_remove_orphans() {
    $this->cliService->drush_webform_remove_orphans();
  }

  /****************************************************************************/
  // drush webform:docs. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:docs
   */
  public function drush_webform_docs_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_docs_validate'], $arguments);
  }

  /**
   * Generates HTML documentation.
   *
   * @command webform:docs
   * @usage webform:repair
   *   Generates HTML documentation used by the Webform module's documentation pages.
   * @aliases wfd,webform-docs
   */
  public function drush_webform_docs() {
    $this->cliService->drush_webform_docs();
  }

  /****************************************************************************/
  // drush webform:composer:update. DO NOT EDIT.
  /****************************************************************************/

  /**
   * @hook validate webform:composer:update
   */
  public function drush_webform_composer_update_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    array_shift($arguments);
    call_user_func_array([$this->cliService, 'drush_webform_composer_update_validate'], $arguments);
  }

  /**
   * Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.
   *
   * @command webform:composer:update
   * @option disable-tls If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.
   * @usage webform:composer:update
   *   Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.
   * @aliases wfcu,webform-composer-update
   */
  public function drush_webform_composer_update(array $options = ['disable-tls' => FALSE]) {
    $this->cliService->drush_webform_composer_update();
  }

  /****************************************************************************/
  // drush webform:generate:commands. DO NOT EDIT.
  /****************************************************************************/

  /**
   * Generate Drush commands from webform.drush.inc for Drush 8.x to WebformCommands for Drush 9.x.
   *
   * @command webform:generate:commands
   * @usage drush webform:generate:commands
   *   Generate Drush commands from webform.drush.inc for Drush 8.x to WebformCommands for Drush 9.x.
   * @aliases wfgc,webform-generate-commands
   */
  public function drush_webform_generate_commands() {
    $this->cliService->drush_webform_generate_commands();
  }

}