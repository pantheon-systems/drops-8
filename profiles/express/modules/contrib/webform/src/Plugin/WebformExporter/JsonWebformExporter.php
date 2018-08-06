<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a JSON document exporter.
 *
 * @WebformExporter(
 *   id = "json",
 *   label = @Translation("JSON documents"),
 *   description = @Translation("Exports results as JSON documents."),
 *   archive = TRUE,
 *   options = FALSE,
 * )
 */
class JsonWebformExporter extends DocumentBaseWebformExporter {

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $file_name = $this->getSubmissionBaseName($webform_submission) . '.json';
    $json = Json::encode($webform_submission->toArray(TRUE, TRUE));

    $archiver = new ArchiveTar($this->getArchiveFilePath(), 'gz');
    $archiver->addString($file_name, $json);
  }

}
