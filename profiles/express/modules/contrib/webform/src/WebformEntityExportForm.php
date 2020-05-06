<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export webform configuration.
 */
class WebformEntityExportForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['yaml'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t("Here is your webform's configuration:"),
      '#description' => $this->t('Filename: %file', ['%file' => $this->getConfigName() . '.yml']),
      '#default_value' => $this->getYaml(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element['download'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
      '#button_type' => 'primary',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content = $this->getYaml();
    $filename = $this->getConfigName() . '.yml';
    $headers = [
      'Content-Type' => 'text/yaml',
      'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
    ];
    $response = new Response($content, 200, $headers);
    $form_state->setResponse($response);
  }

  /**
   * Get the webform's raw data.
   *
   * @return string
   *   The webform's raw data.
   */
  protected function getYaml() {
    $config_name = $this->getConfigName();
    $data = $this->config($config_name)->getRawData();
    return WebformYaml::encode($data);
  }

  /**
   * Get the webform's config file name (without *.yml).
   *
   * @return string
   *   The webform's config file name (without *.yml).
   */
  protected function getConfigName() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;
    $definition = $this->entityTypeManager->getDefinition('webform');
    return $definition->getConfigPrefix() . '.' . $webform->getConfigTarget();
  }

}
