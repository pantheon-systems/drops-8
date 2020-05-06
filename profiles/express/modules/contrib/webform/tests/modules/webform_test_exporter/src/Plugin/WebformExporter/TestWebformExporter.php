<?php

namespace Drupal\webform_test_exporter\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformExporter\TableWebformExporter;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a test exporter.
 *
 * @WebformExporter(
 *   id = "test",
 *   label = @Translation("Test"),
 *   description = @Translation("Test exporter results as an HTML table in reverse column order."),
 * )
 */
class TestWebformExporter extends TableWebformExporter {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'reverse' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Reverse the table's column order"),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['reverse'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildHeader() {
    $header = parent::buildHeader();
    return ($this->configuration['reverse']) ? array_reverse($header) : $header;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRecord(WebformSubmissionInterface $webform_submission) {
    $record = parent::buildRecord($webform_submission);
    return ($this->configuration['reverse']) ? array_reverse($record) : $record;
  }

}
