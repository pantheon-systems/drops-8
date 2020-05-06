<?php

namespace Drupal\webform_submission_export_import;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines an interface for exporting webform submission results.
 */
interface WebformSubmissionExportImportImporterInterface {

  /**
   * Set the webform whose submissions are being imported.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   */
  public function setWebform(WebformInterface $webform = NULL);

  /**
   * Get the webform whose submissions are being imported.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform();

  /**
   * Set the webform source entity whose submissions are being imported.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform's source entity.
   */
  public function setSourceEntity(EntityInterface $entity = NULL);

  /**
   * Get the webform source entity whose submissions are being imported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A webform's source entity.
   */
  public function getSourceEntity();

  /**
   * Get the URI of the CSV import file.
   *
   * @return string
   *   The URI of the CSV import file.
   */
  public function getImportUri();

  /**
   * Set the URI of the CSV import file.
   *
   * @param string $uri
   *   The URI of the CSV import file.
   */
  public function setImportUri($uri);

  /**
   * Attempt delete managed file created for import uri.
   *
   * @return bool
   *   TRUE  if managed file created for import uri was deleted.
   */
  public function deleteImportUri();

  /**
   * Get import options.
   *
   * @return array
   *   Import options.
   */
  public function getImportOptions();

  /**
   * Set import options.
   *
   * @param array $options
   *   Import options.
   */
  public function setImportOptions(array $options);

  /**
   * Get import option value.
   *
   * @param string $name
   *   The import option name.
   *
   * @return mixed
   *   The import option value or the default value.
   */
  public function getImportOption($name);

  /**
   * Get default import options.
   *
   * @return array
   *   Default import options.
   */
  public function getDefaultImportOptions();

  /****************************************************************************/
  // Webform field definitions and elements.
  /****************************************************************************/

  /**
   * Get a webform's field definitions.
   *
   * @return array
   *   An associative array containing a webform's field definitions.
   */
  public function getFieldDefinitions();

  /**
   * Get webform elements.
   *
   * @return array
   *   An associative array containing webform elements keyed by name.
   */
  public function getElements();

  /****************************************************************************/
  // Export.
  /****************************************************************************/

  /**
   * Create CSV export header.
   *
   * @return array
   *   The CSV export header columns.
   */
  public function exportHeader();

  /**
   * Export webform submission as a CSV record.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $export_options
   *   An associative array of export options.
   *
   * @return array
   *   The webform submission converted to a CSV record.
   */
  public function exportSubmission(WebformSubmissionInterface $webform_submission, array $export_options = []);

  /****************************************************************************/
  // Import.
  /****************************************************************************/

  /**
   * Import records from CSV import file.
   *
   * @param int $offset
   *   Line to be begin importing from.
   * @param int|null $limit
   *   The number of records to be imported.
   *
   * @return array
   *   An associate array containing imports states including total,
   *   created, updated, skipped, and errors.
   */
  public function import($offset = 0, $limit = NULL);

  /****************************************************************************/
  // Summary.
  /****************************************************************************/

  /**
   * Total number of submissions to be imported.
   *
   * @return int
   *   The total number of submissions to be imported.
   */
  public function getTotal();

  /**
   * Get source (CSV) columns name.
   *
   * @return array
   *   An associative array containing source (CSV) columns name.
   */
  public function getSourceColumns();

  /**
   * Get destination (field and element) columns name.
   *
   * @return array
   *   An associative array containing destination (field and element)
   *   columns name.
   */
  public function getDestinationColumns();

  /**
   * Get source (CSV) to destination (field and element) column mapping.
   *
   * @return array
   *   An associative array containing source (CSV) to
   *   destination (field and element) column mapping.
   */
  public function getSourceToDestinationColumnMapping();

  /****************************************************************************/
  // Batch.
  /****************************************************************************/

  /**
   * Get the number of submissions to be exported with each batch.
   *
   * @return int
   *   Number of submissions to be exported with each batch.
   */
  public function getBatchLimit();

  /**
   * Determine if webform submissions must be imported using batch processing.
   *
   * @return bool
   *   TRUE if webform submissions must be imported using batch processing.
   */
  public function requiresBatch();

}
