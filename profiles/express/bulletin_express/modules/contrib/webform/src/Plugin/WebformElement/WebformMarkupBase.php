<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'markup' element.
 */
abstract class WebformMarkupBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Markup settings.
      'display_on' => 'form',
    ] + $this->getDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);

    // Hide markup element is it should be only displayed on 'view'.
    if (isset($element['#display_on']) && $element['#display_on'] == 'view') {
      $element['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, $value, array $options = []) {
    // Hide markup element if it should be only displayed on a 'form'.
    if (empty($element['#display_on']) || $element['#display_on'] == 'form') {
      return [];
    }

    // Since we are not passing this element to the
    // webform_container_base_html template we need to replace the default
    // sub elements with the value (ie renderable sub elements).
    if (is_array($value)) {
      $element = $value + $element;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, $value, array $options = []) {
    // Hide markup element if it should be only displayed on a 'form'.
    if (empty($element['#display_on']) || $element['#display_on'] == 'form') {
      return [];
    }

    // Must remove #prefix and #suffix.
    unset($element['#prefix'], $element['#suffix']);

    // Since we are not passing this element to the
    // webform_container_base_text template we need to replace the default
    // sub elements with the value (ie renderable sub elements).
    if (is_array($value)) {
      $element = $value + $element;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Markup settings'),
    ];
    $form['markup']['display_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Display on'),
      '#options' => [
        'form' => t('form only'),
        'display' => t('viewed submission only'),
        'both' => t('both form and viewed submission'),
      ],
    ];
    return $form;
  }

}
