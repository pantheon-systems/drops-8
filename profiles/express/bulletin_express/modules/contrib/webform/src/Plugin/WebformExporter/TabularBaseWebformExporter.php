<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\webform\WebformExporterBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines abstract tabular exporter used to build CSV files and HTML tables.
 */
abstract class TabularBaseWebformExporter extends WebformExporterBase {

  use FileHandleTraitWebformExporter;

  /**
   * An associative array containing webform elements keyed by name.
   *
   * @var array
   */
  protected $elements;

  /**
   * An associative array containing a webform's field definitions.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /****************************************************************************/
  // Header.
  /****************************************************************************/

  /**
   * Build export header using webform submission field definitions and webform element columns.
   *
   * @return array
   *   An array containing the export header.
   */
  protected function buildHeader() {
    $export_options = $this->getConfiguration();
    $this->fieldDefinitions = $this->getFieldDefinitions();
    $elements = $this->getElements();

    $header = [];
    foreach ($this->fieldDefinitions as $field_definition) {
      // Build a webform element for each field definition so that we can
      // use WebformElement::buildExportHeader(array $element, $export_options).
      $element = [
        '#type' => ($field_definition['type'] == 'entity_reference') ? 'entity_autocomplete' : 'element',
        '#admin_title' => '',
        '#title' => (string) $field_definition['title'],
        '#webform_key' => (string) $field_definition['name'],
      ];
      $header = array_merge($header, $this->elementManager->invokeMethod('buildExportHeader', $element, $export_options));
    }

    // Build element columns headers.
    foreach ($elements as $element) {
      $header = array_merge($header, $this->elementManager->invokeMethod('buildExportHeader', $element, $export_options));
    }
    return $header;
  }

  /****************************************************************************/
  // Record.
  /****************************************************************************/

  /**
   * Build export record using a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An array containing the export record.
   */
  protected function buildRecord(WebformSubmissionInterface $webform_submission) {
    $export_options = $this->getConfiguration();
    $this->fieldDefinitions = $this->getFieldDefinitions();
    $elements = $this->getElements();

    $record = [];

    // Build record field definition columns.
    foreach ($this->fieldDefinitions as $field_definition) {
      $this->formatRecordFieldDefinitionValue($record, $webform_submission, $field_definition);
    }

    // Build record element columns.
    $data = $webform_submission->getData();
    foreach ($elements as $column_name => $element) {
      $value = (isset($data[$column_name])) ? $data[$column_name] : '';
      $record = array_merge($record, $this->elementManager->invokeMethod('buildExportRecord', $element, $value, $export_options));
    }
    return $record;
  }

  /**
   * Get the field definition value from a webform submission entity.
   *
   * @param array $record
   *   The record to be added to the export file.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $field_definition
   *   The field definition for the value.
   */
  protected function formatRecordFieldDefinitionValue(array &$record, WebformSubmissionInterface $webform_submission, array $field_definition) {
    $export_options = $this->getConfiguration();

    $field_name = $field_definition['name'];
    $field_type = $field_definition['type'];
    switch ($field_type) {
      case 'created':
      case 'changed':
        $record[] = date('Y-m-d H:i:s', $webform_submission->get($field_name)->value);
        break;

      case 'entity_reference':
        $element = [
          '#type' => 'entity_autocomplete',
          '#target_type' => $field_definition['target_type'],
        ];
        $value = $webform_submission->get($field_name)->target_id;
        $record = array_merge($record, $this->elementManager->invokeMethod('buildExportRecord', $element, $value, $export_options));
        break;

      case 'entity_url':
      case 'entity_title':
        if (empty($webform_submission->entity_type->value) || empty($webform_submission->entity_id->value)) {
          $record[] = '';
          break;
        }
        $entity_type = $webform_submission->entity_type->value;
        $entity_id = $webform_submission->entity_id->value;
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
        if ($entity) {
          $record[] = ($field_type == 'entity_url' && $entity->hasLinkTemplate('canonical')) ? $entity->toUrl()->setOption('absolute', TRUE)->toString() : $entity->label();
        }
        else {
          $record[] = '';
        }
        break;

      default:
        $record[] = $webform_submission->get($field_name)->value;
        break;
    }
  }

  /****************************************************************************/
  // Webform definitions and elements.
  /****************************************************************************/

  /**
   * Get a webform's field definitions.
   *
   * @return array
   *   An associative array containing a webform's field definitions.
   */
  protected function getFieldDefinitions() {
    if (isset($this->fieldDefinitions)) {
      return $this->fieldDefinitions;
    }

    $export_options = $this->getConfiguration();

    $this->fieldDefinitions = $this->entityStorage->getFieldDefinitions();
    $this->fieldDefinitions = $this->entityStorage->checkFieldDefinitionAccess($this->getWebform(), $this->fieldDefinitions);
    if ($export_options['excluded_columns']) {
      $this->fieldDefinitions = array_diff_key($this->fieldDefinitions, $export_options['excluded_columns']);
    }

    // Add custom entity reference field definitions which rely on the
    // entity type and entity id.
    if ($export_options['entity_reference_format'] == 'link' && isset($this->fieldDefinitions['entity_type']) && isset($this->fieldDefinitions['entity_id'])) {
      $this->fieldDefinitions['entity_title'] = [
        'name' => 'entity_title',
        'title' => t('Submitted to: Entity title'),
        'type' => 'entity_title',
      ];
      $this->fieldDefinitions['entity_url'] = [
        'name' => 'entity_url',
        'title' => t('Submitted to: Entity URL'),
        'type' => 'entity_url',
      ];
    }

    return $this->fieldDefinitions;
  }

  /**
   * Get webform elements.
   *
   * @return array
   *   An associative array containing webform elements keyed by name.
   */
  protected function getElements() {
    if (isset($this->elements)) {
      return $this->elements;
    }

    $export_options = $this->getConfiguration();
    $this->elements = $this->getWebform()->getElementsInitializedFlattenedAndHasValue('view');
    if ($export_options['excluded_columns']) {
      $this->elements = array_diff_key($this->elements, $export_options['excluded_columns']);
    }
    return $this->elements;
  }

}
