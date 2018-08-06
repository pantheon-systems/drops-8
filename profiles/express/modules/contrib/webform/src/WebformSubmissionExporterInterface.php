<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for exporting webform submission results.
 */
interface WebformSubmissionExporterInterface {

  /**
   * Set the webform whose submissions are being exported.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   */
  public function setWebform(WebformInterface $webform = NULL);

  /**
   * Get the webform whose submissions are being exported.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform();

  /**
   * Set the webform source entity whose submissions are being exported.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform's source entity.
   */
  public function setSourceEntity(EntityInterface $entity = NULL);

  /**
   * Get the webform source entity whose submissions are being exported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A webform's source entity.
   */
  public function getSourceEntity();

  /**
   * Get export options for the current webform and entity.
   *
   * @return array
   *   Export options.
   */
  public function getWebformOptions();

  /**
   * Set export options for the current webform and entity.
   *
   * @param array $options
   *   Export options.
   */
  public function setWebformOptions(array $options = []);

  /**
   * Delete export options for the current webform and entity.
   */
  public function deleteWebformOptions();

  /**
   * Set results exporter.
   *
   * @param array $export_options
   *   Associative array of exporter options.
   *
   * @return \Drupal\webform\Plugin\WebformExporterInterface
   *   A results exporter.
   */
  public function setExporter(array $export_options = []);

  /**
   * Get the results exporter.
   *
   * @return \Drupal\webform\Plugin\WebformExporterInterface
   *   A results exporter.
   */
  public function getExporter();

  /**
   * Get export options.
   *
   * @return array
   *   Export options.
   */
  public function getExportOptions();

  /**
   * Get default export options.
   *
   * @return array
   *   Default export options.
   */
  public function getDefaultExportOptions();

  /**
   * Build export options webform.
   *
   * @param array $form
   *   The webform.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $export_options
   *   The default values.
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options = []);

  /**
   * Get the values from the webform's user input or webform state values.
   *
   * @paran array $input
   *   An associative array of user input or webform state values.
   *
   * @return array
   *   An associative array of export options.
   */
  public function getValuesFromInput(array $values);

  /**
   * Execute results exporter and write export to a temp file.
   */
  public function generate();

  /**
   * Write webform results header to export file.
   */
  public function writeHeader();

  /**
   * Write webform results header to export file.
   *
   * @param \Drupal\webform\WebformSubmissionInterface[] $webform_submissions
   *   A webform submission.
   */
  public function writeRecords(array $webform_submissions);

  /**
   * Write webform results footer to export file.
   */
  public function writeFooter();

  /**
   * Write export file to Archive file.
   */
  public function writeExportToArchive();

  /**
   * Get webform submission query for specified YAMl webform and export options.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A webform submission entity query.
   */
  public function getQuery();

  /**
   * Total number of submissions to be exported.
   *
   * @return int
   *   The total number of submissions to be exported.
   */
  public function getTotal();

  /**
   * Get the number of submissions to be exported with each batch.
   *
   * @return int
   *   Number of submissions to be exported with each batch.
   */
  public function getBatchLimit();

  /**
   * Determine if webform submissions must be exported using batch processing.
   *
   * @return bool
   *   TRUE if webform submissions must be exported using batch processing.
   */
  public function requiresBatch();

  /**
   * Get export file temp directory path.
   *
   * @return string
   *   Temp directory path.
   */
  public function getFileTempDirectory();

  /**
   * Get webform submission base file name.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return string
   *   Webform submission's base file name.
   */
  public function getSubmissionBaseName(WebformSubmissionInterface $webform_submission);

  /**
   * Get export file name and path.
   *
   * @return string
   *   Export file name and path.
   */
  public function getExportFilePath();

  /**
   * Get export file name .
   *
   * @return string
   *   Export file name.
   */
  public function getExportFileName();

  /**
   * Get archive file name and path for a webform.
   *
   * @return string
   *   Archive file name and path for a form
   */
  public function getArchiveFilePath();

  /**
   * Get archive file name for a webform.
   *
   * @return string
   *   Archive file name.
   */
  public function getArchiveFileName();

  /**
   * Determine if an archive is being generated.
   *
   * @return bool
   *   TRUE if an archive is being generated.
   */
  public function isArchive();

  /**
   * Determine if export needs to use batch processing.
   *
   * @return bool
   *   TRUE if export needs to use batch processing.
   */
  public function isBatch();

}
