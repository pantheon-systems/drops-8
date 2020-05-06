<?php

namespace Drupal\webform\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformExporterBase;

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
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['file_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File name'),
      '#description' => $this->t('Used to create unique file names for exported submissions.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['file_name'],
    ];
    return $form;
  }

}
