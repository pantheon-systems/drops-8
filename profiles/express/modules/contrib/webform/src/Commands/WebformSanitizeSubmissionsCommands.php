<?php

namespace Drupal\webform\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Drush sql-sanitize plugin for sanitizing (truncating) webform submissions.
 *
 * @see \Drush\Drupal\Commands\sql\SanitizeSessionsCommands
 */
class WebformSanitizeSubmissionsCommands extends DrushCommands implements SanitizePluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WebformSanitizeSubmissionsCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Sanitize webform submissions from the DB.
   *
   * @hook post-command sql-sanitize
   *
   * @inheritdoc
   */
  public function sanitize($result, CommandData $command_data) {
    $options = $command_data->options();
    if ($this->isEnabled($options['sanitize-webform-submissions'])) {
      $this->database->truncate('webform_submission')->execute();
      $this->database->truncate('webform_submission_data')->execute();
      if ($this->moduleHandler->moduleExists('webform_submission_log')) {
        $this->database->truncate('webform_submission_log')->execute();
      }
      $this->entityTypeManager->getStorage('webform_submission')->resetCache();
      $this->logger()->success(dt('Webform submission tables truncated.'));
    }
  }

  /**
   * @hook option sql-sanitize
   * @option sanitize-webform-submissions
   *   By default, submissions are truncated. Specify 'no' to disable that.
   */
  public function options($options = ['sanitize-webform-submissions' => NULL]) {}

  /**
   * @hook on-event sql-sanitize-confirms
   *
   * @inheritdoc
   */
  public function messages(&$messages, InputInterface $input) {
    $options = $input->getOptions();
    if ($this->isEnabled($options['sanitize-webform-submissions'])) {
      $messages[] = dt('Truncate webform submission tables.');
    }
  }

  /**
   * Test an option value to see if it is disabled.
   *
   * @param string $value
   *   The enabled options value.
   *
   * @return bool
   *   TRUE if santize websubmission is enabled.
   */
  protected function isEnabled($value) {
    return ($value !== 'no');
  }

}
