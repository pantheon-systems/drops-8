<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a YAML document exporter.
 *
 * @WebformExporter(
 *   id = "yaml",
 *   label = @Translation("YAML documents"),
 *   description = @Translation("Exports results as YAML documents."),
 *   archive = TRUE,
 *   options = FALSE,
 * )
 */
class YamlWebformExporter extends DocumentBaseWebformExporter {

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $file_name = $this->getSubmissionBaseName($webform_submission) . '.yml';
    $yaml = Yaml::encode($webform_submission->toArray(TRUE, TRUE));
    $yaml = WebformYaml::tidy($yaml);

    $archiver = new ArchiveTar($this->getArchiveFilePath(), 'gz');
    $archiver->addString($file_name, $yaml);
  }

}
