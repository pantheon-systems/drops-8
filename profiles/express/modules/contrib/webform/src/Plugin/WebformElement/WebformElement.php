<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;

/**
 * Provides a 'generic' element. Used as a fallback.
 *
 * @WebformElement(
 *   id = "webform_element",
 *   label = @Translation("Generic element"),
 *   description = @Translation("Provides a generic form element."),
 * )
 */
class WebformElement extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [];
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return (!empty($element['#type']) && !in_array($element['#type'], ['submit'])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['element'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['custom']['#type'] = 'fieldset';
    $form['custom']['#title'] = $this->t('Element settings');
    $form['custom']['#weight'] = 100;
    $form['custom']['custom']['#title'] = $this->t('Properties');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildConfigurationFormTabs(array $form, FormStateInterface $form_state) {
    // Generic elements do not need use tabs.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
