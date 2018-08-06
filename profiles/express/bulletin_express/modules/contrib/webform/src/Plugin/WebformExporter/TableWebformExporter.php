<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a HTML table exporter.
 *
 * @WebformExporter(
 *   id = "table",
 *   label = @Translation("HTML Table"),
 *   description = @Translation("Exports results as an HTML table."),
 * )
 */
class TableWebformExporter extends TabularBaseWebformExporter {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'excel' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($form['excel'])) {
      return $form;
    }

    $form['excel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open HTML table in Excel'),
      '#description' => $this->t('If checked, the download file extension will be change from .html to .xls.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['excel'],
      '#states' => [
        'visible' => [
          [':input.js-webform-exporter' => ['value' => 'table']],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {
    $header = $this->buildHeader();

    $file_handle = $this->fileHandle;

    if ($this->configuration['source_entity']) {
      $title = $this->configuration['source_entity']->label();
    }
    elseif ($this->configuration['webform']) {
      $title = $this->configuration['webform']->label();
    }
    else {
      $title = '';
    }

    $thead = [];
    foreach ($header as $item) {
      $thead[] = '<th>' . htmlentities($item) . '</th>';
    }

    fwrite($file_handle, '<!doctype html>');
    fwrite($file_handle, '<html>');
    fwrite($file_handle, '<head>');
    fwrite($file_handle, '<meta charset="utf-8">');
    if ($title) {
      fwrite($file_handle, '<title>' . $title . '</title>');
    }
    fwrite($file_handle, '</head>');
    fwrite($file_handle, '<body>');

    fwrite($file_handle, '<table border="1">');
    fwrite($file_handle, '<thead><tr bgcolor="#cccccc" valign="top">');
    fwrite($file_handle, implode(PHP_EOL, $thead));
    fwrite($file_handle, '</tr></thead>');
    fwrite($file_handle, '<tbody>');
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $record = $this->buildRecord($webform_submission);

    $file_handle = $this->fileHandle;

    $row = [];
    foreach ($record as $item) {
      $row[] = '<td>' . nl2br(htmlentities($item)) . '</td>';
    }

    fwrite($file_handle, '<tr valign="top">');
    fwrite($file_handle, implode(PHP_EOL, $row));
    fwrite($file_handle, '</tr>');
  }

  /**
   * {@inheritdoc}
   */
  public function writeFooter() {
    $file_handle = $this->fileHandle;

    fwrite($file_handle, '</tbody>');
    fwrite($file_handle, '</table>');
    fwrite($file_handle, '</body>');
    fwrite($file_handle, '</html>');
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    return ($this->configuration['excel']) ? 'xls' : 'html';
  }

}
