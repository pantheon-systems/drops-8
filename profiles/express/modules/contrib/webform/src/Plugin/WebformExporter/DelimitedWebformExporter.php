<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a delimited text exporter.
 *
 * @WebformExporter(
 *   id = "delimited",
 *   label = @Translation("Delimited text"),
 *   description = @Translation("Exports results as delimited text file."),
 * )
 */
class DelimitedWebformExporter extends TabularBaseWebformExporter {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'delimiter' => ',',
      'excel' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    if ($this->configuration['delimiter'] == '\t') {
      $this->configuration['delimiter'] = "\t";
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('<strong>Warning:</strong> Opening delimited text files with spreadsheet applications may expose you to <a href=":href">formula injection</a> or other security vulnerabilities. When the submissions contain data from untrusted users and the downloaded file will be used with Microsoft Excel, use \'HTML table\' format.', [':href' => 'https://www.google.com/search?q=spreadsheet+formula+injection']),
    ];
    $form['delimiter'] = [
      '#type' => 'select',
      '#title' => $this->t('Delimiter text format'),
      '#description' => $this->t('This is the delimiter used in the CSV/TSV file when downloading webform results. Using tabs in the export is the most reliable method for preserving non-latin characters. You may want to change this to another character depending on the program with which you anticipate importing results.'),
      '#required' => TRUE,
      '#options' => [
        ','  => $this->t('Comma (,)'),
        '\t' => $this->t('Tab (\t)'),
        ';'  => $this->t('Semicolon (;)'),
        ':'  => $this->t('Colon (:)'),
        '|'  => $this->t('Pipe (|)'),
        '.'  => $this->t('Period (.)'),
        ' '  => $this->t('Space ( )'),
      ],
      '#default_value' => $this->configuration['delimiter'],
    ];
    $form['excel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate Excel compatible file'),
      '#description' => $this->t("If checked, the generated file's carriage returns will be compatible with Excel."),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['excel'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    switch ($this->configuration['delimiter']) {
      case "\t":
        return 'tsv';

      default:
        return 'csv';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {
    $header = $this->buildHeader();
    fputcsv($this->fileHandle, $header, $this->configuration['delimiter']);
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $record = $this->buildRecord($webform_submission);
    fputcsv($this->fileHandle, $record, $this->configuration['delimiter']);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRecord(WebformSubmissionInterface $webform_submission) {
    $record = parent::buildRecord($webform_submission);
    if ($this->configuration['excel']) {
      foreach ($record as $index => $value) {
        if (is_string($value)) {
          $record[$index] = str_replace(PHP_EOL, "\r\n", $value);
        }
      }
    }
    return $record;
  }

}
