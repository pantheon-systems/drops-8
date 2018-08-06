<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformExporterBase;

/**
 * Defines abstract document exporter used to export YAML or JSON.
 */
abstract class DocumentBaseWebformExporter extends WebformExporterBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'file_name' => 'submission-[webform_submission:serial]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($form['file_name'])) {
      return $form;
    }

    $form['file_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File name'),
      '#description' => $this->t('Submission file names must be unique.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['file_name'],
      '#states' => [
        'visible' => [
          [':input.js-webform-exporter' => ['value' => 'json']],
          'or',
          [':input.js-webform-exporter' => ['value' => 'yaml']],
        ],
      ],
    ];
    return $form;
  }

}
