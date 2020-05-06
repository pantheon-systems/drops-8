<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for results exporters.
 *
 * @see \Drupal\webform\Annotation\WebformExporter
 * @see \Drupal\webform\Plugin\WebformExporterBase
 * @see \Drupal\webform\Plugin\WebformExporterManager
 * @see \Drupal\webform\Plugin\WebformExporterManagerInterface
 * @see plugin_api
 */
interface WebformExporterInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Tar archive.
   */
  const ARCHIVE_TAR = 'tar';

  /**
   * ZIP file.
   */
  const ARCHIVE_ZIP = 'zip';

  /**
   * Returns the results exporter label.
   *
   * @return string
   *   The results exporter label.
   */
  public function label();

  /**
   * Returns the results exporter description.
   *
   * @return string
   *   The results exporter description.
   */
  public function description();

  /**
   * Checks if the exporter is excluded via webform.settings.
   *
   * @return bool
   *   TRUE if the exporter is excluded.
   */
  public function isExcluded();

  /**
   * Determine if exporter generates an archive.
   *
   * @return bool
   *   TRUE if exporter generates an archive.
   */
  public function isArchive();

  /**
   * Determine if exporter can include uploaded files (in a zipped archive).
   *
   * @return bool
   *   TRUE if exporter can include uploaded files (in a zipped archive).
   */
  public function hasFiles();

  /**
   * Determine if exporter has options.
   *
   * @return bool
   *   TRUE if export has options.
   */
  public function hasOptions();

  /**
   * Returns the results exporter status.
   *
   * @return bool
   *   TRUE is the results exporter is available.
   */
  public function getStatus();

  /**
   * Create export.
   */
  public function createExport();

  /**
   * Open export.
   */
  public function openExport();

  /**
   * Close export.
   */
  public function closeExport();

  /**
   * Write header to export.
   */
  public function writeHeader();

  /**
   * Write submission to export.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission);

  /**
   * Write footer to export.
   */
  public function writeFooter();

  /**
   * Get export file temp directory.
   *
   * @return string
   *   The export file temp directory..
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
   * Get export file extension.
   *
   * @return string
   *   A file extension.
   */
  public function getFileExtension();

  /**
   * Get export base file name without an extension.
   *
   * @return string
   *   A base file name.
   */
  public function getBaseFileName();

  /**
   * Get export file name.
   *
   * @return string
   *   A file name.
   */
  public function getExportFileName();

  /**
   * Get export file path.
   *
   * @return string
   *   A file path.
   */
  public function getExportFilePath();

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
   * Get archive file extension for a webform.
   *
   * @return string
   *   Archive file extension.
   */
  public function getArchiveFileExtension();

  /**
   * Get archive file type.
   *
   * @return string
   *   Archive file type.
   */
  public function getArchiveType();

  /**
   * Add file, directory, or content to exporter archive.
   *
   * @param string $path
   *   System path or file content.
   * @param string $name
   *   Archive path or file name (applies to file content).
   * @param array $options
   *   Tar and zip options.
   */
  public function addToArchive($path, $name, array $options = []);

  /**
   * Get the number of submissions to be exported with each batch.
   *
   * @return int
   *   Number of submissions to be exported with each batch.
   */
  public function getBatchLimit();

}
