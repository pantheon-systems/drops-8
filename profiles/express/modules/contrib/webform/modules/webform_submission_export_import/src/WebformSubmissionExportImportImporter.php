<?php

namespace Drupal\webform_submission_export_import;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformLikert;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Dumper;

/**
 * Webform submission export import manager.
 */
class WebformSubmissionExportImportImporter implements WebformSubmissionExportImportImporterInterface {

  use StringTranslationTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The import file URI.
   *
   * @var string
   */
  protected $importUri;

  /**
   * The total number of records being imported.
   *
   * @var int
   */
  protected $importTotal;

  /**
   * Import options.
   *
   * @var array
   */
  protected $importOptions;

  /**
   * An array containing webform element names.
   *
   * @var array
   */
  protected $elements;

  /**
   * An array containing a webform's field definition names.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a WebformSubmissionExportImport object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, WebformElementManagerInterface $element_manager, FileSystemInterface $file_system) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->elementManager = $element_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebform(WebformInterface $webform = NULL) {
    $this->webform = $webform;
    $this->elementTypes = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity = NULL) {
    $this->sourceEntity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return $this->sourceEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportUri() {
    return $this->importUri;
  }

  /**
   * {@inheritdoc}
   */
  public function setImportUri($uri) {
    $this->importUri = $uri;
    $this->importTotal = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteImportUri() {
    $files = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uri' => $this->getImportUri()]);
    if ($files) {
      $file = reset($files);
      $file->delete();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportOptions() {
    return $this->importOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function setImportOptions(array $options) {
    $this->importOptions = $options + $this->getDefaultImportOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportOption($name) {
    return $this->importOptions[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultImportOptions() {
    return [
      'skip_validation' => FALSE,
      'treat_warnings_as_errors' => FALSE,
      'mapping' => [],
    ];
  }

  /****************************************************************************/
  // Webform field definitions and elements.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    if (isset($this->fieldDefinitions)) {
      return $this->fieldDefinitions;
    }

    $this->fieldDefinitions = $this->entityStorage->getFieldDefinitions();
    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getElements() {
    if (isset($this->elements)) {
      return $this->elements;
    }

    $this->elements = $this->getWebform()
      ->getElementsInitializedFlattenedAndHasValue();
    return $this->elements;
  }

  /****************************************************************************/
  // Export.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function exportHeader() {
    return array_keys($this->getDestinationColumns());
  }

  /**
   * {@inheritdoc}
   */
  public function exportSubmission(WebformSubmissionInterface $webform_submission, array $export_options = []) {
    $submission_data = $webform_submission->toArray(TRUE);

    $record = [];

    // Append fields.
    $field_definitions = $this->getFieldDefinitions();
    foreach ($field_definitions as $field_name => $field_definition) {
      switch ($field_name) {
        case 'uid':
          $value = $this->getEntityExportId($webform_submission->getOwner(), $export_options);
          break;

        case 'entity_id':
          $value = $this->getEntityExportId($webform_submission->getSourceEntity(), $export_options);
          break;

        default:
          $value = (isset($submission_data[$field_name])) ? $submission_data[$field_name] : '';
          break;
      }
      $record[] = $this->exportValue($value);
    }

    // Append elements.
    $elements = $this->getElements();
    foreach ($elements as $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      $has_multiple_values = $element_plugin->hasMultipleValues($element);
      if ($element_plugin instanceof WebformManagedFileBase) {
        // Files: Get File URLS.
        /** @var \Drupal\file\FileInterface $files */
        $files = $element_plugin->getTargetEntities($element, $webform_submission) ?: [];
        $values = [];
        foreach ($files as $file) {
          $values[] = file_create_url($file->getFileUri());
        }
        $value = implode(',', $values);
        $record[] = $this->exportValue($value);
      }
      elseif ($element_plugin instanceof WebformElementEntityReferenceInterface) {
        // Entity references: Get entity UUIDs.
        $entities = $element_plugin->getTargetEntities($element, $webform_submission);
        $values = [];
        foreach ($entities as $entity) {
          $values[] = $this->getEntityExportId($entity, $export_options);
        }
        $value = implode(',', $values);
        $record[] = $this->exportValue($value);
      }
      elseif ($element_plugin instanceof WebformLikert) {
        // Single Composite: Split questions into individual columns.
        $value = $element_plugin->getValue($element, $webform_submission);
        $question_keys = array_keys($element['#questions']);
        foreach ($question_keys as $question_key) {
          $question_value = (isset($value[$question_key])) ? $value[$question_key] : '';
          $record[] = $this->exportValue($question_value);
        }
      }
      elseif ($element_plugin instanceof WebformCompositeBase && !$has_multiple_values) {
        // Composite: Split single composite sub elements into individual columns.
        $value = $element_plugin->getValue($element, $webform_submission);
        $composite_element_keys = array_keys($element_plugin->getCompositeElements());
        foreach ($composite_element_keys as $composite_element_key) {
          $composite_value = (isset($value[$composite_element_key])) ? $value[$composite_element_key] : '';
          $record[] = $this->exportValue($composite_value);
        }
      }
      elseif ($element_plugin->isComposite()) {
        // Composite: Convert multiple composite values to a single line of YAML.
        $value = $element_plugin->getValue($element, $webform_submission);
        $dumper = new Dumper(2);
        $record[] = $dumper->dump($value);
      }
      elseif ($has_multiple_values) {
        // Multiple: Convert to comma separated values with commas URL encodes.
        $values = $element_plugin->getValue($element, $webform_submission);
        $values = ($values !== NULL) ? (array) $values : [];
        foreach ($values as $index => $value) {
          $values[$index] = str_replace(',', '%2C', $value);
        }
        $value = implode(',', $values);
        $record[] = $this->exportValue($value);
      }
      else {
        // Default: Convert NULL values to empty strings.
        $value = $element_plugin->getValue($element, $webform_submission);
        $value = ($value !== NULL) ? $value : '';
        $record[] = $this->exportValue($value);
      }
    }

    return $record;
  }

  /****************************************************************************/
  // Import.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function import($offset = 0, $limit = NULL) {
    if ($limit === NULL) {
      $limit = $this->getBatchLimit();
    }

    $import_options = $this->getImportOptions();

    // Open CSV file.
    $handle = fopen($this->getImportUri(), 'r');

    // Get the column names.
    $column_names = fgetcsv($handle);
    foreach ($column_names as $index => $name) {
      $column_names[$index] = $name;
    }

    // Fast forward CSV file to offset.
    $index = 0;
    while ($index < $offset && !feof($handle)) {
      fgets($handle);
      $index++;
    }

    // Collect import stats.
    $stats = [
      'created' => 0,
      'updated' => 0,
      'skipped' => 0,
      'total' => 0,
      'warnings' => [],
      'errors' => [],
    ];

    // Import submission records.
    while ($stats['total'] < $limit && !feof($handle)) {
      // Get CSV values.
      $values = fgetcsv($handle);
      // Complete ignored empty rows.
      if (empty($values) || $values == ['']) {
        continue;
      }
      $index++;
      $stats['total']++;

      // Track row specific warnings and errors.
      $stats['warnings'][$index] = [];
      $stats['errors'][$index] = [];
      $row_warnings =& $stats['warnings'][$index];
      $row_errors =& $stats['errors'][$index];

      // Make sure expected number of columns and values are equal.
      if (count($column_names) !== count($values)) {
        $t_args = [
          '@expected' => count($column_names),
          '@found' => count($values),
        ];
        $error = $this->t('@expected values expected and only @found found.', $t_args);
        if (!empty($import_options['treat_warnings_as_errors'])) {
          $row_errors[] = $error;
        }
        else {
          $row_warnings[] = $error;
        }
        continue;
      }

      // Create record and trim all values.
      $record = array_combine($column_names, $values);
      foreach ($record as $key => $value) {
        $record[$key] = trim($value);
      }

      // Track original record.
      $original_record = $record;

      // Map.
      $record = $this->importMapRecord($record);

      // Token: Generate token from the original CSV record.
      if (empty($record['token'])) {
        $record['token'] = Crypt::hashBase64(Settings::getHashSalt() . serialize($original_record));
      }

      // Prepare.
      $webform_submission = $this->importLoadSubmission($record);
      if ($errors = $this->importPrepareRecord($record, $webform_submission)) {
        if (!empty($import_options['treat_warnings_as_errors'])) {
          $row_errors = array_merge($row_warnings, array_values($errors));
        }
        else {
          $row_warnings = array_merge($row_warnings, array_values($errors));
        }
      }

      // Validate.
      if (empty($import_options['skip_validation'])) {
        if ($errors = $this->importValidateRecord($record)) {
          $row_errors = array_merge($row_errors, array_values($errors));
        }
      }

      // Skip import if there are row errors.
      if ($row_errors) {
        $stats['skipped']++;
        continue;
      }

      // Save.
      $this->importSaveSubmission($record, $webform_submission);
      $stats[$webform_submission ? 'updated' : 'created']++;
    }

    fclose($handle);
    return $stats;
  }

  /**
   * Map source (CSV) record to destination (submission) records.
   *
   * @param array $record
   *   The source (CSV) record.
   *
   * @return array
   *   The destination (submission) records.
   */
  protected function importMapRecord(array $record) {
    $mapping = $this->getImportOption('mapping');

    // If not mapping is defined return the record AS-IS.
    if (empty($mapping)) {
      return $record;
    }

    $mapped_record = [];
    foreach ($mapping as $source_name => $destination_name) {
      if (isset($record[$source_name])) {
        $mapped_record[$destination_name] = $record[$source_name];
      }
    }
    return $mapped_record;
  }

  /**
   * Load import submission record via UUID or token.
   *
   * @param array $record
   *   The import submission record.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The existing webform submission or NULL if no existing submission found.
   */
  protected function importLoadSubmission(array &$record) {
    $unique_keys = ['uuid', 'token'];
    foreach ($unique_keys as $unique_key) {
      if (!empty($record[$unique_key])) {
        if ($webform_submissions = $this->entityStorage->loadByProperties([$unique_key => $record[$unique_key]])) {
          return reset($webform_submissions);
        }
      }
    }
    return NULL;
  }

  /**
   * Prepare import submission record.
   *
   * @param array $record
   *   The import submission record.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The existing webform submission.
   *
   * @return array
   *   An array of error messages.
   */
  protected function importPrepareRecord(array &$record, WebformSubmissionInterface $webform_submission = NULL) {
    // Track errors.
    $errors = [];

    if (isset($record['uid'])) {
      // Convert user id to internal IDs.
      $record['uid'] = $this->getEntityImportId('user', $record['uid']);
      // Convert empty uid to anonymous user (UID: 0).
      if (empty($record['uid'])) {
        $record['uid'] = 0;
      }
    }

    // Remove empty uuid.
    if (empty($record['uuid'])) {
      unset($record['uuid']);
    }

    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();

    // Set webform id.
    $record['webform_id'] = $webform->id();

    // Set source entity.
    // Load or convert the source entity id to an internal ID.
    if ($source_entity) {
      $record['entity_type'] = $source_entity->getEntityTypeId();
      $record['entity_id'] = $source_entity->id();
    }
    elseif (!empty($record['entity_type']) && isset($record['entity_id'])) {
      $record['entity_id'] = $this->getEntityImportId($record['entity_type'], $record['entity_id']);
      // If source entity_id can't be found, log error, and
      // remove the source  entity_type.
      if ($record['entity_id'] === NULL) {
        $t_args = [
          '@entity_type' => $record['entity_type'],
          '@entity_id' => $record['entity_id'],
        ];
        $errors[] = $this->t('Unable to locate source entity (@entity_type:@entity_id)', $t_args);
        $record['entity_type'] = NULL;
      }
    }

    // Convert record to submission element data.
    $elements = $this->getElements();
    foreach ($record as $name => $value) {
      // Set record value form an element.
      if (isset($elements[$name])) {
        $element = $elements[$name];
        $record[$name] = $this->importElement($element, $value, $webform_submission, $errors);
        continue;
      }

      // Check if record name is a composite element which is
      // delimited using '__'.
      if (strpos($name, '__') === FALSE) {
        continue;
      }

      // Get element and composite key and confirm that the element exists.
      list($element_key, $composite_key) = explode('__', $name);
      if (!isset($elements[$element_key])) {
        continue;
      }

      // Make sure the composite element is not storing multiple values which
      // must use YAML.
      // @see \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporter::importCompositeElement
      $element = $elements[$element_key];
      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin->hasMultipleValues($element)) {
        continue;
      }

      if ($element_plugin instanceof WebformLikert) {
        // Make sure the Likert question exists.
        if (!isset($element['#questions']) || !isset($element['#questions'][$composite_key])) {
          continue;
        }

        $record[$element_key][$composite_key] = $value;
      }
      elseif ($element_plugin instanceof WebformCompositeBase) {
        // Get the composite element and make sure it exists.
        $composite_elements = $element_plugin->getCompositeElements();
        if (!isset($composite_elements[$composite_key])) {
          continue;
        }

        $composite_element = $composite_elements[$composite_key];
        $record[$element_key][$composite_key] = $this->importElement($composite_element, $value, $webform_submission, $errors);
      }
    }

    return $errors;
  }

  /**
   * Import element.
   *
   * @param array $element
   *   A managed file element.
   * @param mixed $value
   *   File URI(s) from CSV record.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Existing submission or NULL if new submission.
   * @param array $errors
   *   An array of error messages.
   *
   * @return array|int|null
   *   An array of multiple files, single file id, or NULL if file could
   *   not be imported.
   */
  protected function importElement(array $element, $value, WebformSubmissionInterface $webform_submission = NULL, array &$errors) {
    $element_plugin = $this->elementManager->getElementInstance($element);

    if ($value === '') {
      // Empty: Convert multiple values to an empty array or NULL value.
      return ($element_plugin->hasMultipleValues($element)) ? [] : NULL;
    }
    elseif ($element_plugin instanceof WebformManagedFileBase) {
      // Files: Convert File URL to file object.
      return $this->importManageFileElement($element, $value, $webform_submission, $errors);
    }
    elseif ($element_plugin instanceof WebformElementEntityReferenceInterface) {
      // Entity references: Convert entity UUIDs to internal IDs.
      return $this->importEntityReferenceElement($element, $value, $webform_submission, $errors);
    }
    elseif ($element_plugin->isComposite()) {
      // Composite: Decode YAML.
      return $this->importCompositeElement($element, $value, $webform_submission, $errors);
    }
    elseif ($element_plugin->hasMultipleValues($element)) {
      // Multiple: Convert to comma separated values to array.
      return $this->importMultipleElement($element, $value, $webform_submission, $errors);
    }
    else {
      return $value;
    }
  }

  /**
   * Import managed file element.
   *
   * @param array $element
   *   A managed file element.
   * @param mixed $value
   *   File URI(s) from CSV record.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Existing submission or NULL if new submission.
   * @param array $errors
   *   An array of error messages.
   *
   * @return array|int|null
   *   An array of multiple files, single file id, or NULL if file could
   *   not be imported.
   */
  protected function importManageFileElement(array $element, $value, WebformSubmissionInterface $webform_submission = NULL, array &$errors) {
    $webform = $this->getWebform();
    $element_plugin = $this->elementManager->getElementInstance($element);

    // Prepare managed file element with a temp submission.
    $element_plugin->prepare($element, $this->entityStorage->create(['webform_id' => $webform->id()]));

    // Get file destination.
    $file_destination = isset($element['#upload_location']) ? $element['#upload_location'] : NULL;
    if (isset($file_destination) && !$this->fileSystem->prepareDirectory($file_destination, FileSystemInterface::CREATE_DIRECTORY)) {
      $this->loggerFactory->get('file')
        ->notice('The upload directory %directory for the file element %name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', [
          '%directory' => $file_destination,
          '%name' => $element['#webform_key'],
        ]);
      return ($element_plugin->hasMultipleValues($element)) ? [] : NULL;
    }

    // Get file upload validators.
    $file_upload_validators = $element['#upload_validators'];

    // Create a uri and sha1 lookup tables for existing files.
    $existing_file_ids = [];
    $existing_file_uris = [];
    $existing_files = ($webform_submission) ? $element_plugin->getTargetEntities($element, $webform_submission) ?: [] : [];
    foreach ($existing_files as $existing_file) {
      $existing_file_uri = file_create_url($existing_file->getFileUri());
      $existing_file_uris[$existing_file_uri] = $existing_file->id();

      $existing_file_hash = sha1_file($existing_file->getFileUri());
      $existing_file_ids[$existing_file_hash] = $existing_file->id();
    }

    // Find or upload new file.
    $new_file_ids = [];
    $new_file_uris = explode(',', $value);
    foreach ($new_file_uris as $new_file_uri) {
      // Check existing file URIs.
      if (isset($existing_file_uris[$new_file_uri])) {
        $new_file_ids[$new_file_uri] = $existing_file_uris[$new_file_uri];
        continue;
      }

      $t_args = [
        '@element_key' => $element['#webform_key'],
        '@url' => $new_file_uri,
      ];

      // Check URL protocol.
      if (!preg_match('#^https?://#', $new_file_uri)) {
        $errors[] = $this->t('[@element_key] Invalid file URL (@url). URLS must begin with http:// or https://.', $t_args);
        continue;
      }

      // Check URL status code.
      $file_headers = @get_headers($new_file_uri);
      if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
        $errors[] = $this->t('[@element_key] URL (@url) returns 404 file not found.', $t_args);
        continue;
      }

      $new_file_hash = @sha1_file($new_file_uri);
      if (!$new_file_hash) {
        $errors[] = $this->t('[@element_key] Unable to read file from URL (@url).', $t_args);
        continue;
      }

      // Check existing file hashes.
      if (isset($existing_file_ids[$new_file_hash])) {
        $new_file_ids[$new_file_hash] = $existing_file_ids[$new_file_hash];
        continue;
      }

      // Write new file URI to server and upload it.
      $temp_file_contents = @file_get_contents($new_file_uri);
      if (!$temp_file_contents) {
        $errors[] = $this->t('[@element_key] Unable to read file from URL (@url).', $t_args);
        continue;
      }

      // Create a temp file.
      $handle = tmpfile();
      fwrite($handle, $temp_file_contents);
      $temp_file_meta_data = stream_get_meta_data($handle);
      $temp_file_path = $temp_file_meta_data['uri'];
      $temp_file_size = filesize($temp_file_path);

      // Mimic Symfony and Drupal's upload file handling.
      $temp_file_info = new UploadedFile($temp_file_path, basename($new_file_uri), NULL, $temp_file_size);
      $webform_element_key = $element_plugin->getLabel($element);
      $new_file = _webform_submission_export_import_file_save_upload_single($temp_file_info, $webform_element_key, $file_upload_validators, $file_destination);
      if ($new_file) {
        $new_file_ids[$new_file_hash] = $new_file->id();
      }
    }

    $values = array_values($new_file_ids);
    if (empty($values)) {
      return ($element_plugin->hasMultipleValues($element)) ? [] : NULL;
    }
    else {
      return ($element_plugin->hasMultipleValues($element)) ? $values : reset($values);
    }
  }

  /**
   * Import entity reference element.
   *
   * @param array $element
   *   A managed file element.
   * @param mixed $value
   *   File URI(s) from CSV record.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Existing submission or NULL if new submission.
   * @param array $errors
   *   An array of error messages.
   *
   * @return array|int|null
   *   An array of entity ids, a single entity id, or NULL if entity ids
   *   could not be imported.
   */
  protected function importEntityReferenceElement(array $element, $value, WebformSubmissionInterface $webform_submission = NULL, array &$errors) {
    $element_plugin = $this->elementManager->getElementInstance($element);
    $entity_type_id = $element_plugin->getTargetType($element);
    $values = explode(',', $value);
    foreach ($values as $index => $value) {
      $values[$index] = $this->getEntityImportId($entity_type_id, $value);
      if ($values[$index] === NULL) {
        $t_args = [
          '@element_key' => $element['#webform_key'],
          '@entity_id' => $value,
        ];
        $errors[] = $this->t('[@element_key] Unable to locate entity (@entity_id).', $t_args);
        unset($values[$index]);
      }
    }
    $values = array_values($values);
    if (empty($values)) {
      return ($element_plugin->hasMultipleValues($element)) ? [] : NULL;
    }
    else {
      return ($element_plugin->hasMultipleValues($element)) ? $values : reset($values);
    }
  }

  /**
   * Import composite element.
   *
   * @param array $element
   *   A composite element.
   * @param mixed $value
   *   File URI(s) from CSV record.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Existing submission or NULL if new submission.
   * @param array $errors
   *   An array of error messages.
   *
   * @return array
   *   An array of composite element data.
   */
  protected function importCompositeElement(array $element, $value, WebformSubmissionInterface $webform_submission = NULL, array &$errors) {
    try {
      return Yaml::decode($value);
    }
    catch (\Exception $exception) {
      $t_args = [
        '@element_key' => $element['#webform_key'],
        '@error' => $exception->getMessage(),
      ];
      $errors[] = $this->t('[@element_key] YAML is not valid. @error', $t_args);
      return [];
    }
  }

  /**
   * Import multiple element.
   *
   * @param array $element
   *   An element with multiple values.
   * @param mixed $value
   *   File URI(s) from CSV record.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Existing submission or NULL if new submission.
   * @param array $errors
   *   An array of error messages.
   *
   * @return array
   *   An array of multiple values.
   */
  protected function importMultipleElement(array $element, $value, WebformSubmissionInterface $webform_submission = NULL, array &$errors) {
    $values = preg_split('/\s*,\s*/', $value);
    foreach ($values as $index => $item) {
      $values[$index] = str_replace('%2C', ',', $item);
    }
    return $values;
  }

  /**
   * Validate import record submission.
   *
   * @param array $record
   *   The record to be imported.
   *
   * @return array
   *   An array of error messages.
   */
  protected function importValidateRecord(array $record) {
    $values = $this->importConvertRecordToValues($record);
    return WebformSubmissionForm::validateFormValues($values);
  }

  /**
   * Save import record submission.
   *
   * @param array $record
   *   The record to be imported.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The existing webform submission.
   */
  protected function importSaveSubmission(array $record, WebformSubmissionInterface $webform_submission = NULL) {
    $field_definitions = $this->getFieldDefinitions();
    $elements = $this->getElements();

    if ($webform_submission) {
      // Update submission.
      unset($record['sid'], $record['serial'], $record['uuid']);
      foreach ($record as $name => $value) {
        if (isset($field_definitions[$name])) {
          $webform_submission->set($name, $value);
        }
        elseif (isset($elements[$name])) {
          $webform_submission->setElementData($name, $value);
        }
      }
    }
    else {
      // Create submission.
      unset($record['sid'], $record['serial']);
      $values = $this->importConvertRecordToValues($record);
      $webform_submission = $this->entityStorage->create($values);
    }
    $webform_submission->save();
  }

  /**
   * Convert CSV records to entity values.
   *
   * @param array $record
   *   The record to be imported.
   *
   * @return array
   *   The CSV records converted to entity values.
   *
   * @see \Drupal\webform\Entity\WebformSubmission::preCreate
   */
  protected function importConvertRecordToValues(array $record) {
    $field_definitions = $this->getFieldDefinitions();
    $elements = $this->getElements();

    $values = ['data' => []];
    foreach ($record as $name => $value) {
      if (isset($field_definitions[$name])) {
        $values[$name] = $value;
      }
      elseif (isset($elements[$name])) {
        $values['data'][$name] = $value;
      }
    }

    // Never allow the record to set the sid or serial.
    unset($values['sid'], $values['serial']);

    return $values;
  }

  /****************************************************************************/
  // Summary.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    if (isset($this->importTotal)) {
      return $this->importTotal;
    }
    // Ignore the header.
    $total = -1;
    $handle = fopen($this->importUri, 'r');
    while (!feof($handle)) {
      $line = fgets($handle);
      if (!empty(trim($line))) {
        $total++;
      }
    }
    $this->importTotal = $total;
    return $this->importTotal;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceColumns() {
    $file = fopen($this->getImportUri(), 'r');
    $values = fgetcsv($file);
    fclose($file);
    return array_combine($values, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationColumns() {
    $columns = [];

    $field_definitions = $this->getFieldDefinitions();
    foreach ($field_definitions as $field_name => $field_definition) {
      $columns[$field_name] = $field_definition['title'];
    }

    $elements = $this->getElements();
    foreach ($elements as $element_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      $element_title = $element_plugin->getAdminLabel($element);
      $has_multiple_values = $element_plugin->hasMultipleValues($element);
      if (!$has_multiple_values && $element_plugin instanceof WebformCompositeBase) {
        $composite_elements = $element_plugin->getCompositeElements();
        foreach ($composite_elements as $composite_element_key => $composite_element) {
          $composite_element_name = $element_key . '__' . $composite_element_key;
          $composite_element_plugin = $this->elementManager->getElementInstance($composite_element);
          $composite_element_title = $composite_element_plugin->getAdminLabel($composite_element);
          $t_args = [
            '@element_title' => $element_title,
            '@composite_title' => $composite_element_title,
          ];
          $columns[$composite_element_name] = $this->t('@element_title: @composite_title', $t_args);
        }
      }
      elseif (!$has_multiple_values && $element_plugin instanceof WebformLikert) {
        $questions = $element['#questions'];
        foreach ($questions as $question_key => $question) {
          $question_element_name = $element_key . '__' . $question_key;
          $t_args = [
            '@element_title' => $element_title,
            '@question_title' => $question,
          ];
          $columns[$question_element_name] = $this->t('@element_title: @question_title', $t_args);
        }
      }
      else {
        $columns[$element_key] = $element_title;
      }
    }

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceToDestinationColumnMapping() {
    $source_column_names = $this->getSourceColumns();
    $destination_column_names = $this->getDestinationColumns();

    // Map source to destination columns.
    $mapping = [];
    foreach ($source_column_names as $source_column_name) {
      if (isset($destination_column_names[$source_column_name])) {
        $mapping[$source_column_name] = $source_column_name;
      }
      else {
        $mapping[$source_column_name] = '';
      }
    }

    return $mapping;
  }

  /****************************************************************************/
  // Batch.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getBatchLimit() {
    return $this->configFactory->get('webform.settings')
      ->get('batch.default_batch_import_size') ?: 100;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresBatch() {
    return ($this->getTotal() > $this->getBatchLimit()) ? TRUE : FALSE;
  }

  /****************************************************************************/
  // Helpers.
  /****************************************************************************/

  /**
   * Get an entity's export id or UUID based on the export options.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param array $export_options
   *   Export options.
   *
   * @return string|int
   *   The entity's id or UUID.
   */
  protected function getEntityExportId(EntityInterface $entity = NULL, array $export_options = []) {
    if (!$entity) {
      return '';
    }
    else {
      return (empty($export_options['uuid'])) ? $entity->id() : $entity->uuid();
    }
  }

  /**
   * Get an entity's import internal id.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity id or UUID.
   *
   * @return int|string|null
   *   An entity's internal id. NULL if an entity's internal id
   *   can't be determined.
   */
  protected function getEntityImportId($entity_type, $entity_id) {
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      return NULL;
    }

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Load entity by properties.
    if ($entity_type === 'user') {
      $properties = ['uuid', 'mail', 'name'];
    }
    else {
      $properties = ['uuid'];
    }
    foreach ($properties as $property) {
      $entities = $entity_storage->loadByProperties([$property => $entity_id]);
      if ($entities) {
        $entity = reset($entities);
        return $entity->id();
      }
    }

    // Load entity by internal id.
    $entity = $entity_storage->load($entity_id);
    if ($entity) {
      return $entity->id();
    }

    return NULL;
  }

  /**
   * Export value so that it can be editted in Excel and Google Sheets.
   *
   * @param string $value
   *   A value.
   *
   * @return string
   *   A value that it can be editted in Excel and Googl Sheets.
   */
  protected function exportValue($value) {
    // Prevent Excel and Google Sheets from convert string beginning with
    // + or - into formulas by adding a space before the string.
    // @see https://stackoverflow.com/questions/4438589/bypass-excel-csv-formula-conversion-on-fields-starting-with-or
    if (is_string($value) && strpos($value, '+') === 0 || strpos($value, '-') === 0) {
      return ' ' . $value;
    }
    else {
      return $value;
    }
  }

}
