<?php

namespace Drupal\webform_test_variant\Plugin\WebformVariant;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformVariantBase;
use Drupal\webform\WebformInterface;

/**
 * Webform example variant.
 *
 * @WebformVariant(
 *   id = "test",
 *   label = @Translation("Test"),
 *   category = @Translation("Test"),
 *   description = @Translation("Test of a webform variant."),
 * )
 */
class TestWebformVariant extends WebformVariantBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(WebformInterface $webform) {
    // Only allow variant to be applicable to webform_test_variant_ webforms.
    return (strpos($webform->id(), 'test_variant_') === 0 || strpos($webform->id(), 'example_variant_') === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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

    // Debug.
    $this->debug();

    return TRUE;
  }

  /****************************************************************************/
  // Debug and exception handlers.
  /****************************************************************************/

  /**
   * Display debugging information.
   */
  protected function debug() {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $this->messenger()->addWarning('The test variant has been applied');
  }

}
