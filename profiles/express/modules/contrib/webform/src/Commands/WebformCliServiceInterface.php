<?php

namespace Drupal\webform\Commands;

use Drush\Commands\DrushCommands;

/**
 * Defines an interface for Drush version agnostic commands.
 */
interface WebformCliServiceInterface {

  /**
   * Set the Drush 9.x command.
   *
   * @param \Drush\Commands\DrushCommands $command
   *   A Drush 9.x command.
   */
  public function setCommand(DrushCommands $command);

  /**
   * Implements hook_drush_command().
   */
  public function webform_drush_command();

  /**
   * Implements drush_hook_COMMAND_validate().
   */
  public function drush_webform_export_validate($webform_id = NULL);

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_export($webform_id = NULL);

  /**
   * Implements drush_hook_COMMAND_validate().
   */
  public function drush_webform_purge_validate($webform_id = NULL);

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_purge($webform_id = NULL);

  /**
   * Implements drush_hook_COMMAND_validate().
   */
  public function drush_webform_tidy_validate($target = NULL);

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_tidy($target = NULL);

  /**
   * Implements drush_hook_COMMAND_validate().
   */
  public function drush_webform_generate_validate($webform_id = NULL);

  /**
   * Implements drush_hook_COMMAND().
   */
  function drush_webform_generate($webform_id = NULL, $num = NULL);

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_libraries_status();

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_libraries_make();

  /**
   * Implements drush_hook_COMMAND().
   *
   * How to handle module library dependencies #68
   *
   * @see https://github.com/drupal-composer/drupal-project/issues/68
   */
  public function drush_webform_libraries_composer();

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_libraries_download();

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_libraries_remove($status = NULL);

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_repair();

  /**
   * Implements drush_hook_COMMAND_validate().
   */
  public function drush_webform_docs_validate();

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_docs();

  /**
   * Implements drush_hook_COMMAND_validate().
   */
  public function drush_webform_composer_update_validate();

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_composer_update();

  /**
   * Implements drush_hook_COMMAND().
   */
  public function drush_webform_generate_commands();

}
