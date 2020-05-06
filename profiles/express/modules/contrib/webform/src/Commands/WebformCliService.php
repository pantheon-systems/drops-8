<?php

namespace Drupal\webform\Commands;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Cache\Cache;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Controller\WebformResultsExportController;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Form\WebformResultsClearForm;
use Drupal\webform\Form\WebformSubmissionsPurgeForm;
use Drupal\webform\Utility\WebformObjectHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform_submission_export_import\Form\WebformSubmissionExportImportUploadForm;
use Drush\Commands\DrushCommands;
use Psr\Log\LogLevel;

/**
 * Drush version agnostic commands.
 */
class WebformCliService implements WebformCliServiceInterface {

  /**
   * A Drush 9.x command.
   *
   * @var \Drupal\webform\Commands\WebformCommands
   */
  protected $command;

  /**
   * Set the drush 9.x command.
   *
   * @param \Drupal\webform\Commands\WebformCommands $command
   *   A Drush 9.x command.
   */
  public function setCommand(DrushCommands $command) {
    $this->command = $command;
  }

  /**
   * Constructs a WebformCliService object.
   */
  public function __construct() {
    // @todo Add dependency injections.
  }

  /**
   * Call WebformCommand method or drush function.
   *
   * @param string $name
   *   Function name.
   * @param array $arguments
   *   Function arguments.
   *
   * @return mixed
   *   Return function results.
   *
   * @throws \Exception
   *   Throw exception if WebformCommand method and drush function is not found.
   */
  public function __call($name, array $arguments) {
    if ($this->command && method_exists($this->command, $name)) {
      return call_user_func_array([$this->command, $name], $arguments);
    }
    elseif (function_exists($name)) {
      return call_user_func_array($name, $arguments);
    }
    else {
      throw new \Exception("Unknown method/function '$name'.");
    }
  }

  /****************************************************************************/
  // Commands.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function webform_drush_command() {
    $items = [];

    /* Submissions */

    $items['webform-export'] = [
      'description' => 'Exports webform submissions to a file.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
      'arguments' => [
        'webform' => 'The webform ID you want to export (required unless --entity-type and --entity-id are specified)',
      ],
      'options' => [
        'exporter' => 'The type of export. (delimited, table, yaml, or json)',
        // Delimited export options.
        'delimiter' => 'Delimiter between columns (defaults to site-wide setting). This option may need to be wrapped in quotes. i.e. --delimiter="\t".',
        'multiple-delimiter' => 'Delimiter between an element with multiple values (defaults to site-wide setting).',
        // Document and managed file export options.
        'file-name' => 'File name used to export submission and uploaded filed. You may use tokens.',
        'archive-type' => 'Archive file type for submission file uploadeds and generated records. (tar or zip)',
        // Tabular export options.
        'header-format' => 'Set to "label" (default) or "key"',
        'options-item-format' => 'Set to "label" (default) or "key". Set to "key" to print select list values by their keys instead of labels.',
        'options-single-format' => 'Set to "separate" (default) or "compact" to determine how single select list values are exported.',
        'options-multiple-format' => 'Set to "separate" (default) or "compact" to determine how multiple select list values are exported.',
        'entity-reference-items' => 'Comma-separated list of entity reference items (id, title, and/or url) to be exported.',
        'excluded-columns' => 'Comma-separated list of component IDs or webform keys to exclude.',
        // CSV options
        'uuid' => ' Use UUIDs for all entity references. (Only applies to CSV download)',
        // Download options.
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
        // Output options.
        'destination' => 'The full path and filename in which the CSV or archive should be stored. If omitted the CSV file or archive will be outputted to the command line.',
      ],
      'aliases' => ['wfx'],
    ];

    $items['webform-import'] = [
      'description' => 'Imports webform submissions from a CSV file.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
      'arguments' => [
        'webform' => 'The webform ID you want to import (required unless --entity-type and --entity-id are specified)',
        'import_uri' => 'The path or URI for the CSV file to be imported.',
      ],
      'options' => [
        // Import options.
        'skip_validation' => 'Skip form validation.',
        'treat_warnings_as_errors' => 'Treat all warnings as errors.',
        // Source entity options.
        'entity-type' => 'The entity type to which this submission was submitted from.',
        'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
      ],
      'aliases' => ['wfi'],
    ];

    $items['webform-purge'] = [
      'description' => "Purge webform submissions from the databases",
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
      'arguments' => [
        'webform_id' => "A webform machine name. If not provided, user may choose from a list of names.",
      ],
      'options' => [
        'all' => '[boolean] Flush all submissions',
        'entity-type' => 'The entity type for webform submissions to be purged',
        'entity-id' => 'The ID of the entity for webform submissions to be purged',
      ],
      'examples' => [
        'drush webform-purge' => 'Pick a webform and then purge its submissions.',
        'drush webform-purge contact' => "Delete 'Contact' webform submissions.",
        'drush webform-purge --all' => 'Purge all webform submissions.',
      ],
      'aliases' => ['wfp'],
    ];

    /* Tidy */

    $items['webform-tidy'] = [
      'description' => "Tidy export webform configuration files",
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'options' => [
        'dependencies' => '[boolean] Add module dependencies to installed webform and options configuration entities.',
        'prefix' => 'Prefix for file names to be tidied. (Defaults to webform)',
      ],
      'arguments' => [
        'target' => "The module (config/install), config directory (sync), or path (/some/path) that needs its YAML configuration files tidied. (Defaults to webform)",
      ],
      'examples' => [
        'drush webform-tidy webform' => "Tidies YAML configuration files in 'webform/config' for the Webform module",
      ],
      'aliases' => ['wft'],
    ];

    /* Libraries */

    $items['webform-libraries-status'] = [
      'description' => 'Displays the status of third party libraries required by the Webform module.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-libraries-status' => 'Displays the status of third party libraries required by the Webform module.',
      ],
      'aliases' => ['wfls'],
    ];

    $items['webform-libraries-make'] = [
      'description' => 'Generates libraries YAML to be included in a drush.make.yml files.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-libraries-make' => 'Generates libraries YAML to be included in a drush.make.yml file.',
      ],
      'aliases' => ['wflm'],
    ];

    $items['webform-libraries-composer'] = [
      'description' => "Generates the Webform module's composer.json with libraries as repositories.",
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'options' => [
        'disable-tls' => '[boolean] If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.',
      ],
      'examples' => [
        'webform-libraries-composer' => "Generates the Webform module's composer.json with libraries as repositories.",
      ],
      'aliases' => ['wflc'],
    ];

    $items['webform-libraries-download'] = [
      'description' => 'Download third party libraries required by the Webform module.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-libraries-download' => 'Download third party libraries required by the Webform module.',
      ],
      'aliases' => ['wfld'],
    ];

    $items['webform-libraries-remove'] = [
      'description' => 'Removes all downloaded third party libraries required by the Webform module.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-libraries-remove' => 'Removes all downloaded third party libraries required by the Webform module.',
      ],
      'aliases' => ['wflr'],
    ];

    /* Devel Generate */

    $items['webform-generate'] = [
      'description' => 'Create submissions in specified webform.',
      'arguments' => [
        'webform_id' => 'Webform id into which new submissions will be inserted.',
        'num' => 'Number of submissions to insert. Defaults to 50.',
      ],
      'options' => [
        'kill' => '[boolean] Delete all submissions in specified webform before generating.',
        'feedback' => 'An integer representing interval for insertion rate logging. Defaults to 1000',
        'entity-type' => 'The entity type to which this submission was submitted from.',
        'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
      ],
      'aliases' => ['wfg'],
    ];

    /* Repair */

    $items['webform-repair'] = [
      'description' => 'Makes sure all Webform admin configuration and webform settings are up-to-date.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-repair' => 'Repairs admin configuration and webform settings are up-to-date.',
      ],
      'aliases' => ['wfr'],
    ];

    $items['webform-remove-orphans'] = [
      'description' => "Removes orphaned submissions where the submission's webform was deleted.",
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-remove-orphans' => "Removes orphaned submissions where the submission's webform was deleted.",
      ],
      'aliases' => ['wfro'],
    ];

    /* Docs */

    $items['webform-docs'] = [
      'description' => 'Generates HTML documentation.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'examples' => [
        'webform-repair' => 'Generates HTML documentation used by the Webform module\'s documentation pages.',
      ],
      'aliases' => ['wfd'],
    ];

    /* Composer */

    $items['webform-composer-update'] = [
      'description' => "Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.",
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
      'options' => [
        'disable-tls' => '[boolean] If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.',
      ],
      'examples' => [
        'webform-composer-update' => "Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.",
      ],
      'aliases' => ['wfcu'],
    ];

    /* Generate commands */

    $items['webform-generate-commands'] = [
      'description' => 'Generate Drush commands from webform.drush.inc for Drush 8.x to WebformCommands for Drush 9.x.',
      'core' => ['8+'],
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
      'examples' => [
        'drush webform-generate-commands' => "Generate Drush commands from webform.drush.inc for Drush 8.x to WebformCommands for Drush 9.x.",
      ],
      'aliases' => ['wfgc'],
    ];

    return $items;
  }

  /******************************************************************************/
  // Export
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_export_validate($webform_id = NULL) {
    return ($webform_id) ? $this->_drush_webform_validate($webform_id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_export($webform_id = NULL) {
    if (!$webform_id) {
      $webforms = array_keys(Webform::loadMultiple());
      $choices = array_combine($webforms, $webforms);
      $webform_id = $this->drush_choice($choices, $this->dt("Choose a webform to export submissions from."));
      if ($webform_id === FALSE) {
        return $this->drush_user_abort();
      }
    }

    $webform = Webform::load($webform_id);
    // @todd Determine if we should get source entity from options entity type
    // and id.
    $source_entity = NULL;

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);

    // Get command options as export options.
    $default_options = $submission_exporter->getDefaultExportOptions();
    $export_options = $this->drush_redispatch_get_options();
    // Convert dashes to underscores.
    foreach ($export_options as $key => $value) {
      unset($export_options[$key]);
      if (isset($default_options[$key]) && is_array($default_options[$key])) {
        $value = explode(',', $value);
      }
      $export_options[str_replace('-', '_', $key)] = $value;
    }
    $export_options += $submission_exporter->getDefaultExportOptions();
    $submission_exporter->setExporter($export_options);

    WebformResultsExportController::batchSet($webform, $source_entity, $export_options);
    $this->drush_backend_batch_process();

    $file_path = ($submission_exporter->isArchive()) ? $submission_exporter->getArchiveFilePath() : $submission_exporter->getExportFilePath();
    if (isset($export_options['destination'])) {
      $this->drush_print($this->dt('Created @destination', ['@destination' => $export_options['destination']]));
      \Drupal::service('file_system')->copy($file_path, $export_options['destination'], FileSystemInterface::EXISTS_REPLACE);
    }
    else {
      $this->drush_print(file_get_contents($file_path));
    }
    @unlink($file_path);

    return NULL;
  }

  /******************************************************************************/
  // Import.
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_import_validate($webform_id = NULL, $import_uri = NULL) {
    if (!\Drupal::moduleHandler()->moduleExists('webform_submission_export_import')) {
      return $this->drush_set_error($this->dt('The Webform Submission Export/Import module must be enabled to perform imports.'));
    }

    if ($errors = $this->_drush_webform_validate($webform_id)) {
      return $errors;
    }

    if (empty($import_uri)) {
      return $this->drush_set_error($this->dt('Please include the CSV path or URI.'));
    }
    if (file_exists($import_uri)) {
      return NULL;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_import($webform_id = NULL, $import_uri = NULL) {
    /** @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $submission_importer */
    $submission_importer = \Drupal::service('webform_submission_export_import.importer');

    // Get webform.
    $webform = Webform::load($webform_id);

    // Get source entity.
    $entity_type = $this->drush_get_option('entity-type');
    $entity_id = $this->drush_get_option('entity-id');
    if ($entity_type && $entity_id) {
      $source_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    }
    else {
      $source_entity = NULL;
    }

    // Get import options
    $import_options = $this->_drush_get_options($submission_importer->getDefaultImportOptions());

    $submission_importer->setWebform($webform);
    $submission_importer->setSourceEntity($source_entity);
    $submission_importer->setImportOptions($import_options);
    $submission_importer->setImportUri($import_uri);;
    $t_args = ['@total' => $submission_importer->getTotal()];
    if (!$this->drush_confirm($this->dt('Are you sure you want to import @total submissions?', $t_args) . PHP_EOL . $this->dt('This action cannot be undone.'))) {
      return $this->drush_user_abort();
    }

    WebformSubmissionExportImportUploadForm::batchSet($webform, $source_entity, $import_uri, $import_options);
    $this->drush_backend_batch_process();

    return NULL;
  }

  /******************************************************************************/
  // Purge
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_purge_validate($webform_id = NULL) {
    // If webform id is set to 'all' or not included skip validation.
    if ($this->drush_get_option('all') || $webform_id == NULL) {
      return;
    }

    return $this->_drush_webform_validate($webform_id);
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_purge($webform_id = NULL) {
    if ($this->drush_get_option('all')) {
      $webform_id = 'all';
    }

    if (!$webform_id) {
      $webforms = array_keys(Webform::loadMultiple());
      $choices = array_combine($webforms, $webforms);
      $choices = array_merge(['all' => 'all'], $choices);
      $webform_id = $this->drush_choice($choices, $this->dt("Choose a webform to purge submissions from."));
      if ($webform_id === FALSE) {
        return $this->drush_user_abort();
      }
    }

    // Set the webform.
    $webform = ($webform_id == 'all') ? NULL : Webform::load($webform_id);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = $entity_type_manager->getStorage('webform_submission');
    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');

    // Make sure there are submissions that need to be deleted.
    if (!$submission_storage->getTotal($webform)) {
      $this->drush_print($this->dt('There are no submissions that need to be deleted.'));
      return;
    }

    if (!$webform) {
      $submission_total = \Drupal::entityQuery('webform_submission')->count()->execute();
      $form_total = \Drupal::entityQuery('webform')->count()->execute();

      $t_args = [
        '@submission_total' => $submission_total,
        '@submissions' => \Drupal::translation()->formatPlural($submission_total, 'submission', 'submissions'),
        '@form_total' => $form_total,
        '@forms' => \Drupal::translation()->formatPlural($form_total, 'webform', 'webforms'),
      ];
      if (!$this->drush_confirm($this->dt('Are you sure you want to delete @submission_total @submissions in @form_total @forms?', $t_args))) {
        return $this->drush_user_abort();
      }

      $form = new WebformResultsClearForm($entity_type_manager, $request_handler);
      $form->batchSet();
      $this->drush_backend_batch_process();
    }
    else {
      // Set source entity.
      $entity_type = $this->drush_get_option('entity-type');
      $entity_id = $this->drush_get_option('entity-id');
      $source_entity = ($entity_type && $entity_id) ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id) : NULL;

      $t_args = [
        '@title' => ($source_entity) ? $source_entity->label() : $webform->label(),
      ];
      if (!$this->drush_confirm($this->dt("Are you sure you want to delete all submissions from '@title' webform?", $t_args))) {
        return $this->drush_user_abort();
      }

      $form = new WebformSubmissionsPurgeForm($entity_type_manager, $request_handler);
      $form->batchSet($webform, $source_entity);
      $this->drush_backend_batch_process();
    }
  }

  /******************************************************************************/
  // Tidy
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_tidy_validate($target = NULL) {
    global $config_directories;

    $target = $target ?: 'webform';

    if (!isset($config_directories[$target])
      && !(\Drupal::moduleHandler()->moduleExists($target) && file_exists(drupal_get_path('module', $target) . '/config'))
      && !file_exists(realpath($target))) {
      $t_args = ['@target' => $target];
      return $this->drush_set_error($this->dt("Unable to find '@target' module (config/install), config directory (sync), or path (/some/path/).", $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_tidy($target = NULL) {
    global $config_directories;

    $target = $target ?: 'webform';
    $prefix = $this->drush_get_option('prefix', 'webform');

    if (isset($config_directories[$target])) {
      $file_directory_path = DRUPAL_ROOT . '/' . $config_directories[$target];
      $dependencies = $this->drush_get_option('dependencies');
    }
    elseif (\Drupal::moduleHandler()->moduleExists($target)) {
      $file_directory_path = drupal_get_path('module', $target) . '/config';
      $dependencies = $this->drush_get_option('dependencies');
    }
    else {
      $file_directory_path = realpath($target);
      $dependencies = FALSE;
    }

    $files = file_scan_directory($file_directory_path, ($prefix) ? '/^' . preg_quote($prefix, '/.') . '.*\.yml$/' : '/.*\.yml$/');
    $this->drush_print($this->dt("Reviewing @count YAML configuration '@prefix.*' files in '@module'.", ['@count' => count($files), '@module' => $target, '@prefix' => $prefix]));

    $total = 0;
    foreach ($files as $file) {
      $original_yaml = file_get_contents($file->uri);
      $tidied_yaml = $original_yaml;

      try {
        $data = Yaml::decode($tidied_yaml);
      }
      catch (\Exception $exception) {
        $message = 'Error parsing: ' . $file->filename . PHP_EOL . $exception->getMessage();
        if (strlen($message) > 255) {
          $message = substr($message, 0, 255) . '…';
        }
        $this->drush_log($message, LogLevel::ERROR);
        $this->drush_print($message);
        continue;
      }

      // Tidy elements.
      if (strpos($file->filename, 'webform.webform.') === 0 && isset($data['elements'])) {
        try {
          $elements = WebformYaml::tidy($data['elements']);
          $data['elements'] = $elements;
        }
        catch (\Exception $exception) {
          // Do nothing.
        }
      }

      // Add module dependency to exporter webform and webform options config entities.
      if ($dependencies && preg_match('/^(webform\.webform\.|webform\.webform_options\.)/', $file->filename)) {
        if (empty($data['dependencies']['enforced']['module']) || !in_array($target, $data['dependencies']['enforced']['module'])) {
          $this->drush_print($this->dt('Adding module dependency to @file…', ['@file' => $file->filename]));
          $data['dependencies']['enforced']['module'][] = $target;
        }
      }

      // Tidy and add new line to the end of the tidied file.
      $tidied_yaml = WebformYaml::encode($data) . PHP_EOL;
      if ($tidied_yaml != $original_yaml) {
        $this->drush_print($this->dt('Tidying @file…', ['@file' => $file->filename]));
        file_put_contents($file->uri, $tidied_yaml);
        $total++;
      }
    }

    if ($total) {
      $this->drush_print($this->dt('@total YAML file(s) tidied.', ['@total' => $total]));
    }
    else {
      $this->drush_print($this->dt('No YAML files needed to be tidied.'));
    }
  }

  /******************************************************************************/
  // Devel Generate.
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_generate_validate($webform_id = NULL) {
    return $this->_drush_webform_validate($webform_id);
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_generate($webform_id = NULL, $num = NULL) {
    $values = [
      'webform_ids' => [$webform_id => $webform_id],
      'num' => $num ?: 50,
      'feedback' => $this->drush_get_option('feedback') ?: 1000,
      'kill' => $this->drush_get_option('kill'),
      'entity-type' => $this->drush_get_option('entity-type'),
      'entity-id' => $this->drush_get_option('entity-id'),
    ];
    /** @var \Drupal\webform\Plugin\DevelGenerate\WebformSubmissionDevelGenerate $instance */
    $instance = \Drupal::service('plugin.manager.develgenerate')->createInstance('webform_submission', []);
    $instance->generate($values);
  }

  /******************************************************************************/
  // Libraries
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_libraries_status() {
    module_load_include('install', 'webform');

    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $requirements = $libraries_manager->requirements();
    $description = $requirements['webform_libraries']['description'];
    $description = strip_tags($description, '<dt><dd><dl>');
    $description = MailFormatHelper::htmlToText($description);

    $this->drush_print($description);
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_libraries_make() {
    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libraries_manager->getLibraries(TRUE);

    $data = [
      'core' => '8.x',
      'api' => 2,
      'libraries' => [],
    ];
    foreach ($libraries as $library_name => $library) {
      $url = $library['download_url']->toString();
      $data['libraries'][$library_name] = [
        'directory_name' => $library_name,
        'destination' => 'libraries',
        'download' => [
          'type' => 'get',
          'url' => $url,
        ],
      ];
    }

    $data = Yaml::encode($data);
    $this->drush_print($data);
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_libraries_composer() {
    // Load existing composer.json file and unset certain properties.
    $composer_path = drupal_get_path('module', 'webform') . '/composer.json';
    $json = file_get_contents($composer_path);
    $data = json_decode($json , FALSE, $this->drush_webform_composer_get_json_encode_options());
    $data = (array) $data;
    unset($data['extra'], $data['require-dev']);
    $data = (object) $data;

    // Set disable tls.
    $this->drush_webform_composer_set_disable_tls($data);

    // Set libraries.
    $data->repositories = (object) [];
    $data->require = (object) [];
    $this->drush_webform_composer_set_libraries($data->repositories, $data->require);
    // Remove _webform property.
    foreach ($data->repositories as &$repository) {
      unset($repository['_webform']);
    }
    $this->drush_print(json_encode($data, $this->drush_webform_composer_get_json_encode_options()));
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_libraries_download() {
    // Remove all existing libraries (including excluded).
    if ($this->drush_webform_libraries_remove(FALSE)) {
      $this->drush_print($this->dt('Removing existing libraries…'));
    }

    $temp_dir = $this->drush_tempdir();

    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libraries_manager->getLibraries(TRUE);
    foreach ($libraries as $library_name => $library) {
      // Skip libraries installed by other modules.
      if (!empty($library['module'])) {
        continue;
      }

      // Download archive to temp directory.
      $download_url = $library['download_url']->toString();
      $this->drush_print("Downloading $download_url");

      $temp_filepath = $temp_dir . '/' . basename(current(explode('?', $download_url, 2)));
      $this->drush_download_file($download_url, $temp_filepath);

      // Extract ZIP archive.
      $download_location = DRUPAL_ROOT . "/libraries/$library_name";
      $this->drush_print("Extracting to $download_location");

      // Extract to temp location.
      $temp_location = $this->drush_tempdir();
      if (!$this->drush_tarball_extract($temp_filepath, $temp_location)) {
        $this->drush_set_error("Unable to extract $library_name");
        return;
      }

      // Move files and directories from temp location to download location.
      // using rename.
      $files = scandir($temp_location);
      // Remove directories (. ..)
      unset($files[0], $files[1]);
      if ((count($files) == 1) && is_dir($temp_location . '/' . current($files))) {
        $temp_location .= '/' . current($files);
      }
      $this->drush_move_dir($temp_location, $download_location);

      // Remove the tarball.
      if (file_exists($temp_filepath)) {
        $this->drush_delete_dir($temp_filepath, TRUE);
      }
    }

    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_libraries_remove($status = NULL) {
    $status = ($status !== FALSE);
    if ($status) {
      $this->drush_print($this->dt('Beginning to remove libraries…'));
    }
    $removed = FALSE;

    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libraries_manager->getLibraries();
    // Manually add deleted libraries, so that they will always be removed.
    $libraries['jquery.word-and-character-counter'] = 'jquery.word-and-character-counter';
    foreach ($libraries as $library_name => $library) {
      $library_path = '/libraries/' . $library_name;
      $library_exists = (file_exists(DRUPAL_ROOT . $library_path)) ? TRUE : FALSE;
      if ($library_exists) {
        $this->drush_delete_dir(DRUPAL_ROOT . $library_path, TRUE);
        $removed = TRUE;
        if ($status) {
          $t_args = [
            '@name' => $library_name,
            '@path' => $library_path,
          ];
          $this->drush_print($this->dt('@name removed from @path…', $t_args));
        }
      }
    }

    if ($removed) {
      drupal_flush_all_caches();
    }
    return $removed;
  }

  /******************************************************************************/
  // Repair.
  /******************************************************************************/

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\Form\AdminConfig\WebformAdminConfigAdvancedForm::submitForm
   */
  public function drush_webform_repair() {
    if (!$this->drush_confirm($this->dt("Are you sure you want repair the Webform module's admin settings and webforms?"))) {
      return $this->drush_user_abort();
    }

    module_load_include('install', 'webform');

    $this->drush_print($this->dt('Repairing webform submission storage schema…'));
    _webform_update_webform_submission_storage_schema();

    $this->drush_print($this->dt('Repairing admin configuration…'));
    _webform_update_admin_settings(TRUE);

    $this->drush_print($this->dt('Repairing webform settings…'));
    _webform_update_webform_settings();

    $this->drush_print($this->dt('Repairing webform handlers…'));
    _webform_update_webform_handler_settings();

    $this->drush_print($this->dt('Repairing webform field storage definitions…'));
    _webform_update_field_storage_definitions();

    $this->drush_print($this->dt('Repairing webform submission storage schema…'));
    _webform_update_webform_submission_storage_schema();

    if (\Drupal::moduleHandler()->moduleExists('webform_entity_print')) {
      $this->drush_print($this->dt('Repairing webform entity print settings…'));
      module_load_include('install', 'webform_entity_print');
      webform_entity_print_install();
    }

    $this->drush_print($this->dt('Removing (unneeded) webform submission translation settings…'));
    _webform_update_webform_submission_translation();

    // Validate all webform elements.
    $this->drush_print($this->dt('Validating webform elements…'));
    /** @var \Drupal\webform\WebformEntityElementsValidatorInterface $elements_validator */
    $elements_validator = \Drupal::service('webform.elements_validator');

    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = Webform::loadMultiple();
    foreach ($webforms as $webform) {
      if ($messages = $elements_validator->validate($webform)) {
        $this->drush_print('  ' . $this->dt('@title (@id): Found element validation errors.', ['@title' => $webform->label(), '@id' => $webform->id()]));
        foreach ($messages as $message) {
          $this->drush_print('  - ' . strip_tags($message));
        }
      }
    }

    Cache::invalidateTags(['rendered']);
    // @todo Remove when that is fixed in https://www.drupal.org/node/2773591.
    \Drupal::service('cache.discovery')->deleteAll();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\Form\AdminConfig\WebformAdminConfigAdvancedForm::submitForm
   */
  public function drush_webform_remove_orphans() {
    $webform_ids = [];
    $config_factory = \Drupal::configFactory();
    foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
      $webform_id = str_replace('webform.webform.', '', $webform_config_name);
      $webform_ids[$webform_id] = $webform_id;
    }

   $sids = \Drupal::database()->select('webform_submission')
      ->fields('webform_submission', ['sid'])
      ->condition('webform_id', $webform_ids, 'NOT IN')
      ->orderBy('sid')
      ->execute()
      ->fetchCol();

    if (!$sids) {
      $this->drush_print($this->dt('No orphaned submission found.'));
      return;
    }

    $t_args = ['@total' => count($sids)];
    if (!$this->drush_confirm($this->dt("Are you sure you want remove @total orphaned webform submissions?", $t_args))) {
      return $this->drush_user_abort();
    }

    $this->drush_print($this->dt('Deleting @total orphaned webform submissions…', $t_args));
    $submissions = WebformSubmission::loadMultiple($sids);
    foreach ($submissions as $submission) {
      $submission->delete();
    }
  }

  /******************************************************************************/
  // Docs.
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_docs_validate() {
    if (!\Drupal::moduleHandler()->moduleExists('readme')) {
      return $this->drush_set_error($this->dt('The README module is required to generate HTML documentation.'));
    }
    if (!class_exists('\tidy')) {
      return $this->drush_set_error($this->dt('The HTML tidy PHP addon is required to generate HTML documentation.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_docs() {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $html_directory_path = drupal_get_path('module', 'webform') . '/html';
    $images_directory_path = "$html_directory_path/images";

    // Create the /html directory.
    if (!file_exists($html_directory_path)) {
      $file_system->mkdir($html_directory_path);
    }
    if (!file_exists($images_directory_path)) {
      $file_system->mkdir($images_directory_path);
    }

    // Generate docs from MarkDown using the README module's ReadmeManager.
    /** @var \Drupal\readme\ReadmeManagerInterface $readme_manager */
    $readme_manager = \Drupal::service('readme.manager');
    $markdown = [
      'features' => 'docs/FEATURES.md',
    ];
    foreach ($markdown as $markdown_name => $markdown_path) {
      $markdown_html = $readme_manager->getHtml('webform', $markdown_path);
      $markdown_html = preg_replace('#^\s*<h2>[^<]+</h2>\s*#', '', $markdown_html);
      $markdown_html = $this->_drush_webform_docs_tidy($markdown_html);
      file_put_contents("$html_directory_path/webform-$markdown_name.html", $markdown_html);
    }

    // Generate docs from WebformHelpManager.
    /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
    $help_manager = \Drupal::service('webform.help_manager');
    $help = [
      'videos' => $help_manager->buildVideos(TRUE),
      'addons' => $help_manager->buildAddOns(TRUE),
      'libraries' => $help_manager->buildLibraries(TRUE),
      'comparison' => $help_manager->buildComparison(TRUE),
    ];

    $index_html = '<h1>Webform Help</h1><ul>';
    foreach ($help as $help_name => $help_section) {
      $help_html = \Drupal::service('renderer')->renderPlain($help_section);
      $help_html = $this->_drush_webform_docs_tidy($help_html);

      if ($help_name == 'videos') {
        // Download YouTube thumbnails so that they can be updated to
        // https://www.drupal.org/files/
        preg_match_all('#https://img.youtube.com/vi/([^/]+)/0.jpg#', $help_html, $matches);
        foreach ($matches[0] as $index => $image_uri) {
          $file_name = 'webform-youtube-' . $matches[1][$index] . '.jpg';
          copy($image_uri, "$images_directory_path/$file_name");
          $help_html = str_replace($image_uri, "https://www.drupal.org/files/$file_name", $help_html);
        }
      }

      file_put_contents("$html_directory_path/webform-$help_name.html", $help_html);
      $index_html .= "<li><a href=\"webform-$help_name.html\">webform-$help_name.html</a></li>";
    }
    $index_html .= '</ul>';
    file_put_contents("$html_directory_path/index.html", $this->_drush_webform_docs_tidy($index_html));

    $this->drush_print("Documents generated to '/$html_directory_path'.");
  }

  /**
   * Tidy an HTML string.
   *
   * @param string $html
   *   HTML string to be tidied.
   *
   * @return string
   *   A tidied HTML string.
   */
  protected function _drush_webform_docs_tidy($html) {
    // Configuration.
    // - http://us3.php.net/manual/en/book.tidy.php
    // - http://tidy.sourceforge.net/docs/quickref.html#wrap
    $config = ['show-body-only' => TRUE, 'wrap' => '10000'];

    $tidy = new \tidy();
    $tidy->parseString($html, $config, 'utf8');
    $tidy->cleanRepair();
    $html = tidy_get_output($tidy);

    // Convert URLs.
    $html = str_replace('"https://www.drupal.org/', '"/', $html);

    // Remove <code> tag nested within <pre> tag.
    $html = preg_replace('#<pre><code>\s*#', "<code>\n", $html);
    $html = preg_replace('#\s*</code></pre>#', "\n</code>", $html);

    // Fix code in webform-libraries.html.
    $html = str_replace(' &gt; ', ' > ', $html);

    // Remove space after <br> tags.
    $html = preg_replace('/(<br[^>]*>)\s+/', '\1', $html);

    // Convert <pre> to <code>.
    $html = preg_replace('#<hr>\s*<pre>([^<]+)</pre>\s+<hr>\s*<br>#s', '<p><code>\1</code></p>' . PHP_EOL, $html);

    // Append footer to HTML document.
    $html .= '<hr />' . PHP_EOL . '<p><em>This documentation was generated by the Webform module and <b>MUST</b> be updated using the `drush webform-docs` command.</em></p>';

    // Add play icon.
    $html = str_replace('>Watch video</a>', ' class="link-button">▶ Watch video</a>', $html);

    return $html;
  }

  /******************************************************************************/
  // Composer.
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_composer_update_validate() {
    $msg = $this->dt('THIS IS AN EXPERIMENTAL DRUSH COMMAND.') . PHP_EOL .
      $this->dt('PLEASE MAKE SURE TO BACKUP YOUR COMPOSER.JSON FILE.') . PHP_EOL .
      $this->dt("Are you sure you want update your Drupal installation's composer.json file?");
    if (!$this->drush_confirm($msg)) {
      return $this->drush_user_abort();
    }

    $drupal_root = $this->drush_get_context('DRUSH_DRUPAL_ROOT');
    if (file_exists($drupal_root . '/composer.json')) {
      $composer_json = $drupal_root . '/composer.json';
      $composer_directory = '';
    }
    elseif (file_exists(dirname($drupal_root) . '/composer.json')) {
      // The "Composer template for Drupal projects" install Drupal in /web'.
      // @see https://github.com/drupal-composer/drupal-project/blob/8.x/composer.json
      $composer_json = dirname($drupal_root) . '/composer.json';
      $composer_directory = basename($drupal_root) . '/';
    }
    else {
      return $this->drush_set_error($this->dt('Unable to locate composer.json'));
    }

    $this->composer_json = $composer_json;
    $this->composer_directory = $composer_directory;
  }

  /**
   * {@inheritdoc}
   */
  public function drush_webform_composer_update() {
    $composer_json = $this->composer_json;
    $composer_directory = $this->composer_directory;

    $json = file_get_contents($composer_json);
    $data = json_decode($json, FALSE, $this->drush_webform_composer_get_json_encode_options());
    if (!isset($data->repositories)) {
      $data->repositories = (object) [];
    }
    if (!isset($data->require)) {
      $data->repositories = (object) [];
    }

    // Add drupal-library to installer paths.
    if (strpos($json, 'type:drupal-library') === FALSE) {
      $library_path = $composer_directory . 'libraries/{$name}';
      $data->extra->{'installer-paths'}->{$library_path}[] = 'type:drupal-library';
    }

    // Get repositories and require.
    $repositories = &$data->repositories;
    $require = &$data->require;

    // Remove all existing _webform repositories.
    foreach ($repositories as $repository_name => $repository) {
      if (!empty($repository->_webform)) {
        $package_name = $repositories->{$repository_name}->package->name;
        unset($repositories->{$repository_name}, $require->{$package_name});
      }
    }

    // Set disable tls.
    $this->drush_webform_composer_set_disable_tls($data);

    // Set libraries.
    $this->drush_webform_composer_set_libraries($repositories, $require);

    file_put_contents($composer_json, json_encode($data, $this->drush_webform_composer_get_json_encode_options()));

    $this->drush_print("$composer_json updated.");
    $this->drush_print('Make sure to run `composer update --lock`.');
  }

  /**
   * Get Composer specific JSON encode options.
   *
   * @return int
   *   Composer specific JSON encode options.
   *
   * @see https://getcomposer.org/apidoc/1.6.2/Composer/Json/JsonFile.html#method_encode
   */
  protected function drush_webform_composer_get_json_encode_options() {
    return JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
  }

  /**
   * Set composer disable tls.
   *
   * This is needed when CKEditor's HTTPS server's SSL is not working properly.
   *
   * @param object $data
   *   Composer JSON data.
   */
  protected function drush_webform_composer_set_disable_tls(&$data) {
    // Remove disable-tls config.
    if (isset($data->config) && isset($data->config->{'disable-tls'})) {
      unset($data->config->{'disable-tls'});
    }
    if ($this->drush_get_option('disable-tls')) {
      $data->config->{'disable-tls'} = TRUE;
    }
  }

  /**
   * Set composer libraries.
   *
   * @param object $repositories
   *   Composer repositories.
   * @param object $require
   *   Composer require.
   */
  protected function drush_webform_composer_set_libraries(&$repositories, &$require) {
    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libraries_manager->getLibraries(TRUE);
    foreach ($libraries as $library_name => $library) {
      // Never overwrite existing repositories.
      if (isset($repositories->{$library_name})) {
        continue;
      }

      // Skip libraries installed by other modules.
      if (!empty($library['module'])) {
        continue;
      }

      $dist_url = $library['download_url']->toString();
      if (preg_match('/\.zip$/', $dist_url)) {
        $dist_type = 'zip';
      }
      elseif (preg_match('/\.tgz$/', $dist_url)) {
        $dist_type = 'tar';
      }
      else {
        $dist_type = 'file';
      }
      $package_version = $library['version'];
      $package_name = (strpos($library_name, '.') === FALSE) ? "$library_name/$library_name" : str_replace('.', '/', $library_name);
      $repositories->$library_name = [
        '_webform' => TRUE,
        'type' => 'package',
        'package' => [
          'name' => $package_name,
          'version' => $package_version ,
          'type' => 'drupal-library',
          'extra' => [
            'installer-name' => $library_name,
          ],
          'dist' => [
            'url' => $dist_url,
            'type' => $dist_type,
          ],
          'require' => [
            'composer/installers' => '~1.0',
          ],
        ],
      ];

      $require->$package_name = '*';
    }
    $repositories = WebformObjectHelper::sortByProperty($repositories);
    $require = WebformObjectHelper::sortByProperty($require);
  }

  /******************************************************************************/
  // Generate commands.
  /******************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function drush_webform_generate_commands() {
    // Drush 8.x.
    $commands = $this->drush_webform_generate_commands_drush8();
    $filepath = DRUPAL_ROOT . '/' . drupal_get_path('module', 'webform') . '/drush/webform.drush.inc';
    file_put_contents($filepath, $commands);
    $this->drush_print("$filepath updated.");

    // Drush 9.x.
    $commands = $this->drush_webform_generate_commands_drush9();
    $filepath = DRUPAL_ROOT . '/' . drupal_get_path('module', 'webform') . '/src/Commands/WebformCommands.php';
    file_put_contents($filepath, $commands);
    $this->drush_print("$filepath updated.");
  }

  /**
   * Generate webform.drush.inc for Drush 8.x.
   *
   * @return string
   *   webform.drush.inc for Drush 8.x.
   *
   * @see drush/webform.drush.inc
   */
  protected function drush_webform_generate_commands_drush8() {
    $items = $this->webform_drush_command();
    $functions = [];
    foreach ($items as $command_key => $command_item) {
      // Command name.
      $functions[] = "
/******************************************************************************/
// drush $command_key. DO NOT EDIT.
/******************************************************************************/";

      // Validate.
      $validate_method = 'drush_' . str_replace('-', '_', $command_key) . '_validate';
      $validate_hook = 'drush_' . str_replace('-', '_', $command_key) . '_validate';
      if (method_exists($this, $validate_method)) {
        $functions[] = "
/**
 * Implements drush_hook_COMMAND_validate().
 */
function $validate_hook() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), '$validate_method'], func_get_args());
}";
      }

      // Commands.
      $command_method = 'drush_' . str_replace('-', '_', $command_key);
      $command_hook = 'drush_' . str_replace('-', '_', $command_key);
      if (method_exists($this, $command_method)) {
        $functions[] = "
/**
 * Implements drush_hook_COMMAND().
 */
function $command_hook() {
  return call_user_func_array([\Drupal::service('webform.cli_service'), '$command_method'], func_get_args());
}";
      }
    }

    // Build commands.
    $drush_command = $this->webform_drush_command();
    foreach ($drush_command as $command_key => &$command_item) {
      $command_item += ['aliases' => []];
      $command_item['aliases'][] = str_replace('-', ':', $command_key);
    }
    $commands = Variable::export($drush_command);
    // Remove [datatypes] which are only needed for Drush 9.x.
    $commands = preg_replace('/\[(boolean)\]\s+/', '', $commands);
    $commands = trim(preg_replace('/^/m', '  ', $commands));

    // Include.
    $functions = implode(PHP_EOL, $functions) . PHP_EOL;

    return "<?php

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
  return $commands;
}
$functions
";
  }

  /**
   * Generate WebformCommands class for Drush 9.x.
   *
   * @return string
   *   WebformCommands class for Drush 9.x.
   *
   * @see \Drupal\webform\Commands\WebformCommands
   */
  protected function drush_webform_generate_commands_drush9() {
    $items = $this->webform_drush_command();

    $methods = [];
    foreach ($items as $command_key => $command_item) {
      $command_name = str_replace('-', ':', $command_key);

      // Set defaults.
      $command_item += [
        'arguments' => [],
        'options' => [],
        'examples' => [],
        'aliases' => [],
      ];

      // Command name.
      $methods[] = "
  /****************************************************************************/
  // drush $command_name. DO NOT EDIT.
  /****************************************************************************/";

      // Validate.
      $validate_method = 'drush_' . str_replace('-', '_', $command_key) . '_validate';
      if (method_exists($this, $validate_method)) {
        $methods[] = "
  /**
   * @hook validate $command_name
   */
  public function $validate_method(CommandData \$commandData) {
    \$arguments = \$commandData->arguments();
    array_shift(\$arguments);
    call_user_func_array([\$this->cliService, '$validate_method'], \$arguments);
  }";
      }

      // Command.
      $command_method = 'drush_' . str_replace('-', '_', $command_key);
      if (method_exists($this, $command_method)) {
        $command_params = [];
        $command_arguments = [];

        $command_annotations = [];
        // command.
        $command_annotations[] = "@command $command_name";
        // params.
        foreach ($command_item['arguments'] as $argument_name => $argument_description) {
          $command_annotations[] = "@param \$$argument_name $argument_description";
          $command_params[] = "\$$argument_name = NULL";
          $command_arguments[] = "\$$argument_name";
        }
        // options.
        $command_options = [];
        foreach ($command_item['options'] as $option_name => $option_description) {
          $option_default = NULL;
          // Parse [datatype] from option description.
          if (preg_match('/\[(boolean)\]\s+/', $option_description, $match)) {
            $option_description = preg_replace('/\[(boolean)\]\s+/', '', $option_description);
            switch ($match[1]) {
              case 'boolean':
                $option_default = FALSE;
                break;
            }
          }

          $command_annotations[] = "@option $option_name $option_description";
          $command_options[$option_name] = $option_default;
        }
        if ($command_options) {
          $command_options = Variable::export($command_options);
          $command_options = preg_replace('/\s+/', ' ', $command_options);
          $command_options = preg_replace('/array\(\s+/', '[', $command_options);
          $command_options = preg_replace('/, \)/', ']', $command_options);
          $command_params[] = "array \$options = $command_options";
        }

        // usage.
        foreach ($command_item['examples'] as $example_name => $example_description) {
          $example_name = str_replace('-', ':', $example_name);
          $command_annotations[] = "@usage $example_name";
          $command_annotations[] = "  $example_description";
        }

        // aliases.
        $aliases = array_merge($command_item['aliases'] ?: [], [str_replace(':', '-', $command_name)]);
        $aliases = array_unique($aliases);
        if ($aliases) {
          $command_annotations[] = "@aliases " . implode(',', $aliases);
        }

        $command_annotations = '   * ' . implode(PHP_EOL . '   * ', $command_annotations);
        $command_params = implode(', ', $command_params);
        $command_arguments = implode(', ', $command_arguments);

        $methods[] = "
  /**
   * {$command_item['description']}
   *
$command_annotations
   */
  public function $command_method($command_params) {
    \$this->cliService->$command_method($command_arguments);
  }";
      }
    }

    // Class.
    $methods = implode(PHP_EOL, $methods) . PHP_EOL;

    return "<?php
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
$methods
}";
  }

  /******************************************************************************/
  // Helper functions.
  /******************************************************************************/

  /**
   * Validate webform_id argument and source entity-type and entity-id options.
   */
  protected function _drush_webform_validate($webform_id = NULL) {
    if (empty($webform_id)) {
      return $this->drush_set_error($this->dt('Webform id required'));
    }

    if (!empty($webform_id) && !Webform::load($webform_id)) {
      return $this->drush_set_error($this->dt('Webform @id not recognized.', ['@id' => $webform_id]));
    }

    $entity_type = $this->drush_get_option('entity-type');
    $entity_id = $this->drush_get_option('entity-id');
    if ($entity_type || $entity_id) {
      if (empty($entity_type)) {
        return $this->drush_set_error($this->dt('Entity type is required when entity id is specified.'));
      }
      if (empty($entity_id)) {
        return $this->drush_set_error($this->dt('Entity id is required when entity type is specified.'));
      }

      $dt_args = [
        '@webform_id' => $webform_id,
        '@entity_type' => $entity_type,
        '@entity_id' => $entity_id,
      ];

      $source_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      if (!$source_entity) {
        return $this->drush_set_error($this->dt('Unable to load @entity_type:@entity_id', $dt_args));
      }

      $dt_args['@title'] = $source_entity->label();

      if (empty($source_entity->webform) || empty($source_entity->webform->target_id)) {
        return $this->drush_set_error($this->dt("'@title' (@entity_type:@entity_id) does not reference a webform.", $dt_args));
      }

      if ($source_entity->webform->target_id != $webform_id) {
        return $this->drush_set_error($this->dt("'@title' (@entity_type:@entity_id) does not have a '@webform_id' webform associated with it.", $dt_args));
      }
    }
    return NULL;
  }

  /**
   * Get drush command options with dashed converted to underscores.
   *
   * @param array $default_options
   *   The commands default options
   *
   * @return array
   *   An associative array of options.
   */
  protected function _drush_get_options(array $default_options) {
    $options = $this->drush_redispatch_get_options();
    // Convert dashes to underscores.
    foreach ($options as $key => $value) {
      unset($options[$key]);
      if (isset($default_options[$key]) && is_array($default_options[$key])) {
        $value = explode(',', $value);
      }
      $options[str_replace('-', '_', $key)] = $value;
    }
    $options += $default_options;
    return $options;
  }

}

// Add dt() function so that the WebformEditorialController can extract
// editorial.
// @see \Drupal\webform_editorial\Controller\WebformEditorialController::drush
if (!function_exists('dt')) {

  /**
   * Rudimentary replacement for Drupal API t() function.
   *
   * @param string $string
   *   String to process, possibly with replacement item.
   *
   * @return string
   *   The processed string.
   */
  function dt($string) {
    return $string;
  }

}
