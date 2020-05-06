<?php

namespace Drupal\webform_submission_export_import\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Upload webform submission export import CSV.
 */
class WebformSubmissionExportImportUploadForm extends ConfirmFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface
   */
  protected $importer;

  /**
   * Constructs a WebformResultsExportController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $importer
   *   The webform submission importer.
   */
  public function __construct(DateFormatterInterface $date_formatter, WebformRequestInterface $request_handler, WebformSubmissionExportImportImporterInterface $importer) {
    $this->dateFormatter = $date_formatter;
    $this->requestHandler = $request_handler;
    $this->importer = $importer;

    // Initialize the importer.
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity();
    $this->importer->setWebform($webform);
    $this->importer->setSourceEntity($source_entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('webform.request'),
      $container->get('webform_submission_export_import.importer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submission_export_import_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'webform_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->setImportUri($form_state);
    if (!$this->getImportUri()) {
      return $this->buildUploadForm($form, $form_state);
    }
    else {
      return $this->buildConfirmForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submit handler is not being used.
    // @see \Drupal\webform_submission_export_import\Form\WebformSubmissionExportImportUploadForm::buildUploadForm
    // @see \Drupal\webform_submission_export_import\Form\WebformSubmissionExportImportUploadForm::buildConfirmForm
  }

  /****************************************************************************/
  // Upload form.
  /****************************************************************************/

  /**
   * Build upload form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing the structure of the form.
   */
  protected function buildUploadForm(array $form, FormStateInterface $form_state) {
    // Warning.
    $form['experimental_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_id' => 'webform_submission_export_import_experimental',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_STATE,
      '#message_message' => $this->t('Importing submissions is a new and experimental feature.') . '<br/><strong>' . $this->t('Please test and review your imported submissions using a development/test server.') . '</strong>',
    ];

    // Details.
    $temporary_maximum_age = $this->config('system.file')->get('temporary_maximum_age');
    $form['details'] = [
      'title' => [
        '#markup' => $this->t('Please note'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('All submission properties and data is optional.'),
          $this->t('If UUIDs are included, existing submissions will always be updated.'),
          $this->t('If UUIDs are not included, already imported and unchanged records will not create duplication submissions.'),
          $this->t('File uploads must use publicly access URLs which begin with http:// or https://.'),
          $this->t('Entity references can use UUIDs or entity IDs.'),
          $this->t('Composite (single) values are annotated using double underscores. (e.g. ELEMENT_KEY__SUB_ELEMENT_KEY)'),
          $this->t('Multiple values are comma delimited with any nested commas URI escaped (%2E).'),
          $this->t('Multiple composite values are formatted using <a href=":href">inline YAML</a>.', [':href' => 'https://en.wikipedia.org/wiki/YAML#Basic_components']),
          $this->t('Import maximum execution time limit is @time.', ['@time' => $this->dateFormatter->formatInterval($temporary_maximum_age)]),
        ],
      ],
    ];

    // Examples.
    $download_url = $this->requestHandler->getCurrentWebformUrl('webform_submission_export_import.results_import.example.download');
    $view_url = $this->requestHandler->getCurrentWebformUrl('webform_submission_export_import.results_import.example.view');
    $t_args = [
      ':href_download' => $download_url->toString(),
      ':href_view' => $view_url->toString(),
    ];
    $form['examples'] = [
      '#markup' => $this->t('<a href=":href_view">View</a> or <a href=":href_download">download</a> an example submission CSV.', $t_args),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Form.
    $form['import'] = [
      '#type' => 'details',
      '#title' => $this->t('Import data source'),
      '#open' => TRUE,
    ];
    $form['import']['import_type'] = [
      '#title' => 'Type',
      '#type' => 'radios',
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#options' => [
        'file' => $this->t('File upload'),
        'url' => $this->t('Remote URL'),
      ],
      '#default_value' => 'file',
    ];
    $form['import']['import_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload Submission CSV file'),
      '#states' => [
        'visible' => [
          ':input[name="import_type"]' => ['value' => 'file'],
        ],
        'required' => [
          ':input[name="import_type"]' => ['value' => 'file'],
        ],
      ],
    ];
    $form['import']['import_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Enter Submission CSV remote URL'),
      '#description' => $this->t('Remote URL could be a <a href=":href">published Google Sheet</a>.', [':href' => 'https://help.aftership.com/hc/en-us/articles/115008490908-CSV-Auto-Fetch-using-Google-Drive-Spreadsheet']),
      '#states' => [
        'visible' => [
          ':input[name="import_type"]' => ['value' => 'url'],
        ],
        'required' => [
          ':input[name="import_type"]' => ['value' => 'url'],
        ],
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#validate' => ['::validateUploadForm'],
      '#submit' => ['::submitUploadForm'],
    ];

    return $form;
  }

  /**
   * Upload validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateUploadForm(array &$form, FormStateInterface $form_state) {
    $import_type = $form_state->getValue('import_type');
    switch ($import_type) {
      case 'file':
        $files = $this->getRequest()->files->get('files', []);
        if (empty($files['import_file']) || !$files['import_file']->isValid()) {
          $form_state->setErrorByName('import_file', $this->t('The file could not be uploaded.'));
        }
        break;

      case 'url':
        // @todo Determine if remote URL needs to be validated.
        break;
    }
  }

  /**
   * Upload submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitUploadForm(array &$form, FormStateInterface $form_state) {
    $validators = ['file_validate_extensions' => ['csv']];

    $import_type = $form_state->getValue('import_type');

    $file = NULL;
    switch ($import_type) {
      case 'file':
        $files = file_save_upload('import_file', $validators);
        $file = ($files) ? reset($files) : NULL;
        break;

      case 'url':
        $import_url = $form_state->getValue('import_url');
        $file_path = tempnam(file_directory_temp(), 'webform_submission_export_import_') . '.csv';
        file_put_contents($file_path, file_get_contents($import_url));

        $form_field_name = $this->t('Submission CSV (Comma Separated Values) file');
        $file_size = filesize($file_path);
        // Mimic Symfony and Drupal's upload file handling.
        $file_info = new UploadedFile($file_path, basename($file_path), NULL, $file_size);
        $file = _webform_submission_export_import_file_save_upload_single($file_info, $form_field_name, $validators);
        break;
    }

    // If a managed file has been create to the file's id and rebuild the form.
    if ($file) {
      // Normalize carriage returns.
      // This prevent issues with CSV files created in Excel.
      $contents = file_get_contents($file->getFileUri());
      $contents = preg_replace('~\R~u', "\r\n", $contents);
      file_put_contents($file->getFileUri(), $contents);

      $this->importer->setImportUri($file->getFileUri());
      if ($this->importer->getTotal()) {
        $form_state->set('import_fid', $file->id());
        $form_state->setRebuild();
      }
      else {
        $this->messenger()->addError($this->t("Uable to parse CSV file. Please review the CSV file's formatting."));
      }
    }
  }

  /****************************************************************************/
  // Confirm form.
  /****************************************************************************/

  /**
   * Build confirm import form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing the structure of the form.
   */
  protected function buildConfirmForm(array $form, FormStateInterface $form_state) {
    $import_options = $form_state->get('import_options') ?: [];
    $form['#disable_inline_form_errors'] = TRUE;
    $form['#attributes']['class'][] = 'confirmation';
    $form['#theme'] = 'confirm_form';
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // Warning.
    $total = $this->importer->getTotal();
    $t_args = [
      '@submissions' => $this->formatPlural($total, '1 submission', '@total submissions', ['@total' => $total]),
    ];
    $form['warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to import @submissions?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];

    // Details.
    $actions = [
      $this->t('Update submissions that have a corresponding UUID.'),
      $this->t('Create new submissions.'),
    ];
    if (!empty($import_options['skip_validation'])) {
      $actions[] = $this->t('Form validation will be skipped.');
    }
    else {
      $actions[] = $this->t('Skip submissions that are invalid.');
    }
    $form['details'] = [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => $actions,
      ],
    ];

    // Mapping.
    $source = $this->appendNameToOptions($this->importer->getSourceColumns());
    $destination = $this->appendNameToOptions($this->importer->getDestinationColumns());
    $mappings = $this->importer->getSourceToDestinationColumnMapping();
    $form['review'] = [
      '#type' => 'details',
      '#title' => $this->t('Review import'),
    ];
    // Displaying when no UUID or token is found.
    if (!isset($source['uuid']) && !isset($source['uuid'])) {
      $form['review']['warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('No UUID or token was found in the source (CSV). A unique hash will be generated for the each CSV record. Any changes to already an imported record in the source (CSV) will create a new submission.', $t_args),
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_NONE,
      ];
    }
    $form['review']['mapping'] = [
      '#type' => 'webform_mapping',
      '#title' => $this->t('Import mapping'),
      '#source__title' => $this->t('Source (CSV)'),
      '#destination__title' => $this->t('Destination (Submission)'),
      '#description' => $this->t('Please review and select the imported CSV source column to destination element mapping'),
      '#description_display' => 'before',
      '#default_value' => $mappings,
      '#required' => TRUE,
      '#source' => $source,
      '#destination' => $destination,
      '#parents' => ['import_options', 'mapping'],
    ];

    // Options.
    $form['import_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Import options'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['import_options']['skip_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip form validation'),
      '#description' => $this->t('Skipping form validation can cause invalid data to be stored in the database.'),
      '#return_value' => TRUE,
    ];
    $form['import_options']['treat_warnings_as_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Treat all warnings as errors'),
      '#description' => $this->t("CSV data that can't be converted to submission data will display a warning. If checked, these warnings will be treated as errors and prevent the submission from being created."),
      '#return_value' => TRUE,
    ];

    // Confirm.
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to import these submissions'),
      '#required' => TRUE,
    ];

    // Actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
      '#submit' => ['::submitImportForm'],
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
    return $form;
  }

  /**
   * Import submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state) {
    $this->setImportUri($form_state);

    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity();
    $import_uri = $this->importer->getImportUri();
    $import_options = $form_state->getValue('import_options');

    $redirect_url = $this->requestHandler->getCurrentWebformUrl('webform.results_submissions');
    $form_state->setRedirectUrl($redirect_url);

    if ($this->importer->requiresBatch()) {
      static::batchSet($webform, $source_entity, $import_uri, $import_options);
    }
    else {
      $this->importer->setImportOptions($import_options);
      $stats = $this->importer->import();
      static::displayStats($stats);
      $this->importer->deleteImportUri();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // Do not alter the form's title.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Import');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<current>');
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Set the CSV file URI.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state containing the upload file id.
   */
  protected function setImportUri(FormStateInterface $form_state) {
    $fid = $form_state->get('import_fid');
    if (!empty($fid)) {
      $file = File::load($fid);
      $this->importer->setImportUri($file->getFileUri());
    }
  }

  /**
   * Get the CSV file URI.
   *
   * @return string
   *   The CSV file URI.
   */
  protected function getImportUri() {
    return $this->importer->getImportUri();
  }

  /**
   * Append option name to the displayed value.
   *
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An array of options with the option name appended to the displayed value.
   */
  protected function appendNameToOptions(array $options) {
    foreach ($options as $name => $value) {
      if ($name !== (string) $value) {
        $options[$name] .= ' [' . $name . ']';
      }
    }
    return $options;
  }

  /****************************************************************************/
  // Batch functions.
  // Using static method to prevent the service container from being serialized.
  // "Prevents exception 'AssertionError' with message 'The container was serialized.'."
  /****************************************************************************/

  /**
   * Batch API; Initialize batch operations.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform source entity.
   * @param string $import_uri
   *   The URI of the CSV import file.
   * @param array $import_options
   *   An array of import options.
   *
   * @see http://www.jeffgeerling.com/blogs/jeff-geerling/using-batch-api-build-huge-csv
   */
  public static function batchSet(WebformInterface $webform, EntityInterface $source_entity = NULL, $import_uri = '', array $import_options = []) {
    $parameters = [
      $webform,
      $source_entity,
      $import_uri,
      $import_options,
    ];
    $batch = [
      'title' => t('Importing submissions'),
      'init_message' => t('Initializing submission import'),
      'error_message' => t('The import could not be completed because an error occurred.'),
      'operations' => [
        [['\Drupal\webform_submission_export_import\Form\WebformSubmissionExportImportUploadForm', 'batchProcess'], $parameters],
      ],
      'finished' => ['\Drupal\webform_submission_export_import\Form\WebformSubmissionExportImportUploadForm', 'batchFinish'],
    ];

    batch_set($batch);
  }

  /**
   * Batch API callback; Write the header and rows of the export to the export file.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform source entity.
   * @param string $import_uri
   *   The URI of the CSV import file.
   * @param array $import_options
   *   An associative array of import options.
   * @param mixed|array $context
   *   The batch current context.
   */
  public static function batchProcess(WebformInterface $webform, EntityInterface $source_entity = NULL, $import_uri = '', array $import_options = [], &$context) {
    /** @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $importer */
    $importer = \Drupal::service('webform_submission_export_import.importer');
    $importer->setWebform($webform);
    $importer->setSourceEntity($source_entity);
    $importer->setImportUri($import_uri);
    $importer->setImportOptions($import_options);

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['offset'] = 0;
      $context['sandbox']['max'] = $importer->getTotal();
      // Drush is losing the results so we are going to track theme
      // via the sandbox.
      $context['sandbox']['stats'] = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'total' => 0,
        'warnings' => [],
        'errors' => [],
      ];
      $context['results'] = [
        'import_uri' => $import_uri,
      ];
    }

    // Import CSV records.
    $import_stats = $importer->import($context['sandbox']['offset'], $importer->getBatchLimit());

    // Append import stats and errors to results.
    foreach ($import_stats as $import_stat => $value) {
      if (is_array($value)) {
        // Convert translatable markup into strings to save memory.
        $context['sandbox']['stats'][$import_stat] += WebformOptionsHelper::convertOptionsToString($value);
      }
      else {
        $context['sandbox']['stats'][$import_stat] += $value;
      }
    }

    // Track progress.
    $context['sandbox']['progress'] += $import_stats['total'];
    $context['sandbox']['offset'] += $importer->getBatchLimit();

    // Display message.
    $context['message'] = t('Imported @count of @total submissions…', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    // Track finished.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }

    // Context results are not being passed to batchFinish via Drush,
    // therefor we are going to show them when this is finished.
    if ($context['finished'] >= 1) {
      static::displayStats($context['sandbox']['stats']);
    }
  }

  /**
   * Batch API callback; Completed export.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   */
  public static function batchFinish($success, array $results, array $operations) {
    if (!$success) {
      \Drupal::messenger()->addStatus(t('Finished with an error.'));
    }

    // Delete import URI.
    if (isset($results['import_uri'])) {
      $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $results['import_uri']]);
      foreach ($files as $file) {
        $file->delete();
      }
    }
  }

  /**
   * Disply import status.
   *
   * @param array $stats
   *   Import stats.
   */
  public static function displayStats(array $stats) {
    $is_cli = (PHP_SAPI === 'cli');
    $number_of_errors = 0;
    $error_limit = ($is_cli) ? NULL : 50;
    $t_args = [
      '@total' => $stats['total'],
      '@created' => $stats['created'],
      '@updated' => $stats['updated'],
      '@skipped' => $stats['skipped'],
    ];
    if ($is_cli) {
      \Drupal::logger('webform')->notice(t('Submission import completed. (total: @total; created: @created; updated: @updated; skipped: @skipped)', $t_args));
    }
    else {
      \Drupal::messenger()->addStatus(t('Submission import completed. (total: @total; created: @created; updated: @updated; skipped: @skipped)', $t_args));
    }
    $message_types = [
      'warnings' => MessengerInterface::TYPE_WARNING,
      'errors' => MessengerInterface::TYPE_ERROR,
    ];
    foreach ($message_types as $message_group => $message_type) {
      foreach ($stats[$message_group] as $row_number => $messages) {
        $row_prefix = [
          '#markup' => t('Row #@number', ['@number' => $row_number]),
          '#prefix' => $is_cli ? '' : '<strong>',
          '#suffix' => $is_cli ? ': ' : ':</strong> ',
        ];
        foreach ($messages as $message) {
          if ($is_cli) {
            $message = strip_tags($message);
          }
          $build = [
            'row' => $row_prefix,
            'message' => ['#markup' => $message],
          ];
          $message = \Drupal::service('renderer')->renderPlain($build);
          if ($is_cli) {
            \Drupal::logger('webform_submission_export_import')->$message_type($message);
          }
          else {
            \Drupal::messenger()->addMessage($message, $message_type);
          }
          if ($error_limit && ++$number_of_errors >= $error_limit) {
            return;
          }
        }
      }
    }
  }

}
