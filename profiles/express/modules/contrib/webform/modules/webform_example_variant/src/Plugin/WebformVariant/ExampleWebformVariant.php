<?php

namespace Drupal\webform_example_variant\Plugin\WebformVariant;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformVariantBase;
use Drupal\webform\WebformInterface;

/**
 * Webform example variant.
 *
 * @WebformVariant(
 *   id = "example",
 *   label = @Translation("Example"),
 *   category = @Translation("Example"),
 *   description = @Translation("Example of a webform variant."),
 * )
 */
class ExampleWebformVariant extends WebformVariantBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'debug' => FALSE,
      'description__markup' => '',
      'notes__type' => 'textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(WebformInterface $webform) {
    // Only allow variant to be applicable to webform_example_variant_ webforms.
    return (strpos($webform->id(), 'webform_example_variant_') === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['example'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Example settings'),
    ];
    $form['example']['description__markup'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Description'),
      '#parents' => ['settings', 'description__markup'],
      '#default_value' => $this->configuration['description__markup'],
    ];
    $form['example']['notes__type'] = [
      '#type' => 'select',
      '#title' => $this->t('Notes element type'),
      '#options' => [
        'textarea' => $this->t('Text area'),
        'textfield' => $this->t('Text field'),
      ],
      '#empty_value' => '',
      '#default_value' => $this->configuration['notes__type'],
      '#parents' => ['settings', 'notes__type'],
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, variant information will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
      '#parents' => ['settings', 'debug'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
    $this->configuration['debug'] = (boolean) $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function applyVariant() {
    $webform = $this->getWebform();
    if (!$this->isApplicable($webform)) {
      return FALSE;
    }

    // Set description markup.
    $description_markup = $this->configuration['description__markup'];
    $description_element = $webform->getElementDecoded('description');
    if ($description_element && $description_markup) {
      $description_element['#markup'] = $description_markup;
      $webform->setElementProperties('description', $description_element);
    }

    // Set notes type.
    $notes_type = $this->configuration['notes__type'];
    $notes_element = $webform->getElementDecoded('notes');
    if ($notes_element && $notes_type) {
      $notes_element['#type'] = $notes_type;
      $webform->setElementProperties('notes', $notes_element);
    }

    return TRUE;
  }

}
