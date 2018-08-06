<?php

/**
 * @file
 * Webform module drush commands.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Controller\WebformResultsExportController;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Form\WebformResultsClearForm;
use Drupal\webform\Form\WebformSubmissionsPurgeForm;
use Drupal\webform\Utility\WebformYaml;
use Psr\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function webform_drush_command() {
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
      // Delimited export options.
      'delimiter' => 'Delimiter between columns (defaults to site-wide setting). This option may need to be wrapped in quotes. i.e. --delimiter="\t".',
      'multiple_delimiter' => 'Delimiter between an element with multiple values (defaults to site-wide setting).',

      // Document and managed file export options.
      'file-name' => 'File name used to export submission and uploaded filed. You may use tokens.',
      // Tabular export options.
      'header-keys' => 'Set to "label" (default) or "key"',
      'options-item-format' => 'Set to "label" (default) or "key". Set to "key" to print select list values by their keys instead of labels.',
      'options-single-format' => 'Set to "separate" (default) or "compact" to determine how single select list values are exported.',
      'options-multiple-format' => 'Set to "separate" (default) or "compact" to determine how multiple select list values are exported.',
      'entity-reference-items' => 'Comma-separated list of entity reference items (id, title, and/or url) to be exported.',
      'excluded-columns' => 'Comma-separated list of component IDs or webform keys to exclude.',
      // Download options.
      'entity-type' => 'The entity type to which this submission was submitted from.',
      'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
      'range-type' => 'Range of submissions to export: "all", "latest", "serial", "sid", or "date".',
      'range-latest' => 'Integer specifying the latest X submissions will be downloaded. Used if "range-type" is "latest" or no other range options are provided.',
      'range-start' => 'The submission ID or start date at which to start exporting.',
      'range-end' => 'The submission ID or end date at which to end exporting.',
      'state' => 'Submission state to be included: "completed", "draft" or "all" (default).',
      'sticky' => 'Flagged/starred submission status.',
      'files' => 'Download files: "1" or "0" (default). If set to 1, the exported CSV file and any submission file uploads will be download in a gzipped tar file.',
      // Output options.
      'destination' => 'The full path and filename in which the CSV or archive should be stored. If omitted the CSV file or archive will be outputted to the commandline.',
    ],
    'aliases' => ['wfx'],
  ];

  $items['webform-purge'] = [
    'description' => "Purge webform submissions from the databases",
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'arguments' => [
      'webform_id' => "A webform machine name. If not provided, user may choose from a list of names.",
    ],
    'options' => [
      'all' => 'Flush all submissions',
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
      'dependencies' => 'Add module dependencies to installed webform and options configuration entities.',
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
    'callback' => 'drush_devel_generate',
    'callback arguments' => [
      'plugin_id' => 'webform_submission',
    ],
    'description' => 'Create submissions in specified webform.',
    'arguments' => [
      'webform_id' => 'Webform id into which new submissions will be inserted.',
      'num' => 'Number of submissions to insert. Defaults to 50.',
    ],
    'options' => [
      'kill' => 'Delete all submissions in specified webform before generating.',
      'feedback' => 'An integer representing interval for insertion rate logging. Defaults to 1000',
      'entity-type' => 'The entity type to which this submission was submitted from.',
      'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
    ],
    'aliases' => ['wfg'],
  ];

  /* Repair */

  $items['webform-repair'] = [
    'description' => 'Makes sure all Webform admin settings and webforms are up-to-date.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'webform-repair' => 'Repairs admin settings and webforms are up-to-date.',
    ],
    'aliases' => ['wfr'],
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
    'examples' => [
      'webform-composer-update' => "Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.",
    ],
    'aliases' => ['wfcu'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function webform_drush_help($section) {
  switch ($section) {
    case 'drush:webform-export':
      return dt('This command will export webform submissions to a file.');

    case 'drush:webform-purge':
      return dt('This command will purge webform submissions.');

    case 'drush:webform-libraries-status':
      return dt('This command displays the status of third party libraries required by the Webform module.');

    case 'drush:webform-libraries-download':
      return dt('This command downloads third party libraries required by the Webform module.');

    case 'drush:webform-libraries-remove':
      return dt('This command removes all downloaded third party libraries required by the Webform module.');

    case 'drush:webform-libraries-composer-update':
      return dt("Adds the Webform module's libraries as repositories to Drupal's composer.json.");

    case 'drush:webform-repair':
      return dt('Makes sure all Webform admin settings and webforms are up-to-date. Only use this command if you are having problems with your existing Webform configuration.');

    case 'drush:webform-tidy':
      return dt('This command tidies exported YAML configuration files.') . "\n\n" .
        dt('Changes include...') . PHP_EOL .
        dt('- Preserving newlines using pipe (|).') . PHP_EOL .
        dt('- Removing returns after array dashes (-).');

    case 'drush:webform-generate':
      return dt('This command will create submissions in specified webform.');

    case 'drush:webform-composer-update':
      return dt("Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.");

    case 'meta:webform:title':
      return dt('Webform commands');

    case 'meta:webform:summary':
      return dt('Enables the creation of webforms and questionnaires.');
  }
}

/******************************************************************************/
// Export
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_export_validate($webform_id = NULL) {
  return _drush_webform_validate($webform_id);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_export($webform_id = NULL) {
  if (!$webform_id) {
    $webforms = array_keys(Webform::loadMultiple());
    $choices = array_combine($webforms, $webforms);
    $webform_id = drush_choice($choices, dt("Choose a webform to export submissions from."));
    if ($webform_id === FALSE) {
      return drush_user_abort();
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
  $export_options = drush_redispatch_get_options();
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
  drush_backend_batch_process();

  $file_path = ($submission_exporter->isArchive()) ? $submission_exporter->getArchiveFilePath() : $submission_exporter->getExportFilePath();
  if (isset($export_options['destination'])) {
    drush_print(dt('Created @destination', ['@destination' => $export_options['destination']]));
    file_unmanaged_copy($file_path, $export_options['destination'], FILE_EXISTS_REPLACE);
  }
  else {
    drush_print(file_get_contents($file_path));
  }
  @unlink($file_path);

  return NULL;
}

/******************************************************************************/
// Purge
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_purge_validate($webform_id = NULL) {
  // If webform id is set to 'all' or not included skip validation.
  if (drush_get_option('all') || $webform_id == NULL) {
    return;
  }

  return _drush_webform_validate($webform_id);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_purge($webform_id = NULL) {
  if (drush_get_option('all')) {
    $webform_id = 'all';
  }

  if (!$webform_id) {
    $webforms = array_keys(Webform::loadMultiple());
    $choices = array_combine($webforms, $webforms);
    $choices = array_merge(['all' => 'all'], $choices);
    $webform_id = drush_choice($choices, dt("Choose a webform to purge submissions from."));
    if ($webform_id === FALSE) {
      return drush_user_abort();
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
    return drush_set_error(dt('There are no submissions that need to be deleted.'));
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
    if (!drush_confirm(dt('Are you sure you want to delete @submission_total @submissions in @form_total @forms?', $t_args))) {
      return drush_user_abort();
    }

    $form = new WebformResultsClearForm($entity_type_manager, $request_handler);
    $form->batchSet();
    drush_backend_batch_process();
  }
  else {
    // Set source entity.
    $entity_type = drush_get_option('entity-type');
    $entity_id = drush_get_option('entity-id');
    $source_entity = ($entity_type && $entity_id) ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id) : NULL;

    $t_args = [
      '@title' => ($source_entity) ? $source_entity->label() : $webform->label(),
    ];
    if (!drush_confirm(dt("Are you sure you want to delete all submissions from '@title' webform?", $t_args))) {
      return drush_user_abort();
    }

    $form = new WebformSubmissionsPurgeForm($entity_type_manager, $request_handler);
    $form->batchSet($webform, $source_entity);
    drush_backend_batch_process();
  }
}

/******************************************************************************/
// Tidy
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_tidy_validate($target = 'webform') {
  global $config_directories;
  if (!isset($config_directories[$target])
    && !(\Drupal::moduleHandler()->moduleExists($target) && file_exists(drupal_get_path('module', $target) . '/config'))
    && !file_exists(realpath($target))) {
    $t_args = ['@target' => $target];
    return drush_set_error(dt("Unable to find '@target' module (config/install), config directory (sync), or path (/some/path/).", $t_args));
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_tidy($target = 'webform') {
  global $config_directories;
  $prefix = drush_get_option('prefix', 'webform');

  if (isset($config_directories[$target])) {
    $file_directory_path = DRUPAL_ROOT . '/' . $config_directories[$target];
    $dependencies = drush_get_option('dependencies');
  }
  elseif (\Drupal::moduleHandler()->moduleExists($target)) {
    $file_directory_path = drupal_get_path('module', $target) . '/config';
    $dependencies = drush_get_option('dependencies');
  }
  else {
    $file_directory_path = realpath($target);
    $dependencies = FALSE;
  }

  $files = file_scan_directory($file_directory_path, ($prefix) ? '/^' . preg_quote($prefix, '/.') . '.*\.yml$/' : '/.*\.yml$/');
  drush_print(dt("Reviewing @count YAML configuration '@prefix.*' files in '@module'.", ['@count' => count($files), '@module' => $target, '@prefix' => $prefix]));

  $total = 0;
  foreach ($files as $file) {
    $original_yaml = file_get_contents($file->uri);
    $tidied_yaml = $original_yaml;

    // Add module dependency to exporter webform and webform options config entities.
    if ($dependencies && preg_match('/^(webform\.webform\.|webform\.webform_options\.)/', $file->filename)) {
      try {
        $data = Yaml::decode($tidied_yaml);
        if (empty($data['dependencies']['enforced']['module']) || !in_array($target, $data['dependencies']['enforced']['module'])) {
          drush_print(dt('Adding module dependency to @file...', ['@file' => $file->filename]));
          $data['dependencies']['enforced']['module'][] = $target;
          $tidied_yaml = Yaml::encode($data);
        }
      }
      catch (\Exception $exception) {
        $message = 'Error parsing: ' . $file->filename . PHP_EOL . $exception->getMessage();
        if (strlen($message) > 255) {
          $message = substr($message, 0, 255) . '...';
        }
        drush_log($message, LogLevel::ERROR);
        drush_print($message);
      }
    }

    // Tidy and add new line to the end of the tidied file.
    $tidied_yaml = WebformYaml::tidy($tidied_yaml) . PHP_EOL;
    if ($tidied_yaml != $original_yaml) {
      drush_print(dt('Tidying @file...', ['@file' => $file->filename]));
      file_put_contents($file->uri, $tidied_yaml);
      $total++;
    }
  }

  if ($total) {
    drush_print(dt('@total YAML file(s) tidied.', ['@total' => $total]));
  }
  else {
    drush_print(dt('No YAML files needed to be tidied.'));
  }
}

/******************************************************************************/
// Devel Generate.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_generate_validate() {
  // Array of "Callback arguments" and "command line args".
  $params = func_get_args();
  $params[0] = 'webform_submission';
  _drush_plugin_validate($params);
}

/******************************************************************************/
// Libraries
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_status() {
  module_load_include('install', 'webform');

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $requirements = $libraries_manager->requirements(TRUE);
  foreach ($requirements as $requirement) {
    $library = $requirement['library'];
    drush_print($library['title'] . ' (' . $library['name'] . ') ' . $requirement['value']);
    // drush_print();
    drush_print(strip_tags($requirement['description']));
    drush_print();
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_make() {
  drush_print('# This file was generated using the `drush webform-libraries-make` command');
  drush_print(_drush_webform_libraries_make());
}

/**
 * Generate drush make as YAML.
 *
 * @return string
 *   Drush make as YAML
 */
function _drush_webform_libraries_make() {
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

  return Yaml::encode($data);
}

/**
 * Implements drush_hook_COMMAND().
 *
 * How to handle module library dependencies #68
 *
 * @see https://github.com/drupal-composer/drupal-project/issues/68
 */
function drush_webform_libraries_composer() {
  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $libraries = $libraries_manager->getLibraries(TRUE);

  // Load existing composer.json file.
  $data = json_decode('{
  "name": "drupal/webform",
  "description": "Enables the creation of webforms and questionnaires.",
  "type": "drupal-module",
  "license": "GPL-2.0+",
  "minimum-stability": "dev",
  "homepage": "https://drupal.org/project/webform",
  "authors": [
    {
      "name": "Jacob Rockowitz (jrockowitz)",
      "homepage": "https://www.drupal.org/u/jrockowitz",
      "role": "Maintainer"
    }
  ],
  "support": {
    "issues": "https://drupal.org/project/issues/webform",
    "source": "http://cgit.drupalcode.org/webform"
  }
}', TRUE);

  $repositories = [];
  $require = [];
  foreach ($libraries as $library_name => $library) {
    $dist_url = $library['download_url']->toString();
    $dist_type = (preg_match('/\.zip$/', $dist_url)) ? 'zip' : 'file';
    $package_version = $library['version'];
    $package_name = (strpos($library_name, '.') === FALSE) ? "$library_name/$library_name" : str_replace('.', '/', $library_name);

    $repositories[$library_name] = [
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

    $require[$package_name] = $package_version;
  }
  ksort($repositories);
  ksort($require);
  $data['repositories'] = $repositories;
  // Drupal.org test bot is throwing...
  // Your requirements could not be resolved to an installable set of packages.
  $data['require'] = $require;

  drush_print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_download() {
  // Remove all existing libraries.
  if (drush_webform_libraries_remove(FALSE)) {
    drush_print(dt('Removing existing libraries...'));
  }

  // Create the makefile.
  $makefile = file_directory_temp() . '/webform.libraries.make.yml';
  file_put_contents($makefile, _drush_webform_libraries_make());

  // Download libraries using `drush make`.
  $commandline_args = [$makefile, DRUPAL_ROOT];
  $commandline_options = ['no-core' => 'no-core'];
  drush_invoke_process('@self', 'make', $commandline_args, $commandline_options);

  drupal_flush_all_caches();

  // Remove the makefile.
  file_unmanaged_delete($makefile);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_remove($status = TRUE) {
  if ($status) {
    drush_print(dt('Beginning to remove libraries...'));
  }
  $removed = FALSE;

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $libraries = $libraries_manager->getLibraries();
  foreach ($libraries as $library_name => $library) {
    $library_path = '/libraries/' . $library_name;
    $library_exists = (file_exists(DRUPAL_ROOT . $library_path)) ? TRUE : FALSE;
    if ($library_exists) {
      $t_args = [
        '@name' => $library_name,
        '@path' => $library_path,
      ];
      if ($status) {
        drush_print(dt('@name removed from @path...', $t_args));
        drush_delete_dir(DRUPAL_ROOT . $library_path, TRUE);
      }
      $removed = TRUE;
    }
  }

  if ($status) {
    drupal_flush_all_caches();
  }
  return $removed;
}

/******************************************************************************/
// Repair.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_repair() {
  if (!drush_confirm(dt("Are you sure you want repair the Webform module's admin settings and webforms?"))) {
    return drush_user_abort();
  }

  module_load_include('install', 'webform');

  drush_print('Repairing admin settings...');
  _webform_update_admin_settings(TRUE);

  drush_print('Repairing webform settings...');
  _webform_update_webform_settings();

  drush_print('Repairing webform field storage definitions...');
  _webform_update_field_storage_definitions();

  drush_print('Repairing webform submission storage schema...');
  _webform_update_webform_submission_storage_schema();

  // Copied from: outside_in_install().
  drush_print('Repairing quick links...');
  // @todo Remove in https://www.drupal.org/node/2783791.

  Cache::invalidateTags(['rendered']);
  // @todo Remove when that is fixed in https://www.drupal.org/node/2773591.
  \Drupal::service('cache.discovery')->deleteAll();
}

/******************************************************************************/
// Docs.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_docs_validate() {
  if (!\Drupal::moduleHandler()->moduleExists('readme')) {
    return drush_set_error(dt('The README module is required to generate HTML documentation.'));
  }
  if (!class_exists('\tidy')) {
    return drush_set_error(dt('The HTML tidy PHP addon is required to generate HTML documentation.'));
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_docs() {
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
    $markdown_html = _drush_webform_docs_tidy($markdown_html);
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
    $help_html = _drush_webform_docs_tidy($help_html);

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
  file_put_contents("$html_directory_path/index.html", _drush_webform_docs_tidy($index_html));

  drush_print("Documents generated to '/$html_directory_path'.");
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
function _drush_webform_docs_tidy($html) {
  // Configuration.
  // - http://us3.php.net/manual/en/book.tidy.php
  // - http://tidy.sourceforge.net/docs/quickref.html#wrap
  $config = ['show-body-only' => TRUE, 'wrap' => '0'];

  $tidy = new \tidy();
  $tidy->parseString($html, $config, 'utf8');
  $tidy->cleanRepair();
  $html = tidy_get_output($tidy);

  // Convert URLs.
  $html = str_replace('"https://www.drupal.org/', '"/', $html);

  // Remove <code> tag nested within <pre> tag.
  $html = preg_replace('#<pre><code>\s*#', "<code>\n", $html);
  $html = preg_replace('#\s*</code></pre>#', "\n</code>", $html);

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
// Helper functions.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_value().
 */
function drush_webform_composer_update_validate() {
  $msg = dt('THIS IS AN EXPERIMENTAL DRUSH COMMAND.') . PHP_EOL .
    dt('PLEASE MAKE SURE TO BACKUP YOUR COMPOSER.JSON FILE.') . PHP_EOL .
    dt("Are you sure you want update your Drupal installation's composer.json file?");
  if (!drush_confirm($msg)) {
    return drush_user_abort();
  }

  $drupal_root = drush_get_context('DRUSH_DRUPAL_ROOT');
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
    return drush_set_error(dt('Unable to locate composer.json'));
  }

  drush_set_option('composer_json', $composer_json);
  drush_set_option('composer_directory', $composer_directory);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_composer_update() {
  $composer_json = drush_get_option('composer_json');
  $composer_directory = drush_get_option('composer_directory');

  $json = file_get_contents($composer_json);
  $data = Json::decode($json) + [
    'repositories' => [],
    'require' => [],
  ];

  // Add drupal-library to installer paths.
  if (strpos($json, 'type:drupal-library') === FALSE) {
    $data['extra']['installer-paths'][$composer_directory . 'libraries/{$name}'][] = 'type:drupal-library';
  }

  // Get repositories and require.
  $repositories = &$data['repositories'];
  $require = &$data['require'];

  // Remove all existing _webform repositories.
  foreach ($repositories as $repository_name => $repository) {
    if (!empty($repository['_webform'])) {
      $package_name = $repositories[$repository_name]['package']['name'];
      unset($repositories[$repository_name], $require[$package_name]);
    }
  }

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $libraries = $libraries_manager->getLibraries(TRUE);
  // Track if recommended secure_http: true can be used.
  // @see Issue #2885338: Composer: CKeditor plugins are downloadable with http.
  $secure_http = TRUE;
  foreach ($libraries as $library_name => $library) {
    // Never overwrite existing repositories.
    if (isset($repositories[$library_name])) {
      continue;
    }

    $dist_url = $library['download_url']->toString();
    $dist_type = (preg_match('/\.zip$/', $dist_url)) ? 'zip' : 'file';
    $package_version = $library['version'];
    $package_name = (strpos($library_name, '.') === FALSE) ? "$library_name/$library_name" : str_replace('.', '/', $library_name);
    if (strpos($dist_url, 'http://') === 0) {
      $secure_http = FALSE;
    }
    $repositories[$library_name] = [
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

    $require[$package_name] = $package_version;
  }
  ksort($repositories);
  ksort($require);

  if ($secure_http) {
    unset($data['config']['secure-http']);
  }
  else {
    drush_print('Secure HTTP had to be disabled to support CKEditor add-ons.');
    drush_print('@see http://dgo.to/2885338');
    drush_print('@see https://getcomposer.org/doc/06-config.md#secure-http');
    $data['config']['secure-http'] = FALSE;
  }

  $json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
  file_put_contents($composer_json, json_encode($data, $json_options));

  drush_print("$composer_json updated.");
  drush_print('Make sure to run `composer update`.');
}

/******************************************************************************/
// Helper functions.
/******************************************************************************/

/**
 * Validate webform_id argument and source entity-type and entity-id options.
 */
function _drush_webform_validate($webform_id = NULL) {
  if (empty($webform_id)) {
    return drush_set_error(dt('Webform id required'));
  }

  if (!empty($webform_id) && !Webform::load($webform_id)) {
    return drush_set_error(dt('Webform @id not recognized.', ['@id' => $webform_id]));
  }

  $entity_type = drush_get_option('entity-type');
  $entity_id = drush_get_option('entity-id');
  if ($entity_type || $entity_id) {
    if (empty($entity_type)) {
      return drush_set_error(dt('Entity type is required when entity id is specified.'));
    }
    if (empty($entity_id)) {
      return drush_set_error(dt('Entity id is required when entity type is specified.'));
    }

    $dt_args = [
      '@webform_id' => $webform_id,
      '@entity_type' => $entity_type,
      '@entity_id' => $entity_id,
    ];

    $source_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    if (!$source_entity) {
      return drush_set_error(dt('Unable to load @entity_type:@entity_id', $dt_args));
    }

    $dt_args['@title'] = $source_entity->label();

    if (empty($source_entity->webform) || empty($source_entity->webform->target_id)) {
      return drush_set_error(dt("'@title' (@entity_type:@entity_id) does not reference a webform.", $dt_args));
    }

    if ($source_entity->webform->target_id != $webform_id) {
      return drush_set_error(dt("'@title' (@entity_type:@entity_id) does not have a '@webform_id' webform associated with it.", $dt_args));
    }
  }
  return NULL;
}

// Add dt() function so that the WebformEditorialController can extract
// editorial.
// @see \Drupal\webform_editorial\Controller\WebformEditorialController::drush
if (!function_exists('dt')) {
  /**
   * Rudimentary replacement for Drupal API t() function.
   *
   * @param string
   *   String to process, possibly with replacement item.
   *
   * @return
   *   The processed string.
   */
  function dt($string) {return $string;}
}
