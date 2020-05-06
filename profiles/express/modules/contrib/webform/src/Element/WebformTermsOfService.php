<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkbox;

/**
 * Provides a webform terms of service element.
 *
 * @FormElement("webform_terms_of_service")
 */
class WebformTermsOfService extends Checkbox {

  /**
   * Display terms using slideout.
   *
   * @var string
   */
  const TERMS_SLIDEOUT = 'slideout';

  /**
   * Display terms in modal.
   *
   * @var string
   */
  const TERMS_MODAL = 'modal';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#return_value' => TRUE,
      '#terms_type' => static::TERMS_MODAL,
      '#terms_title' => '',
      '#terms_content' => '',
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCheckbox($element) {
    $element = parent::preRenderCheckbox($element);
    $id = 'webform-terms-of-service-' . implode('_', $element['#parents']);

    if (empty($element['#title'])) {
      $element['#title'] = (string) t('I agree to the {terms of service}.');
    }

    $element['#title'] = str_replace('{', '<a role="button" href="#terms">', $element['#title']);
    $element['#title'] = str_replace('}', '</a>', $element['#title']);

    // Change description to render array.
    if (isset($element['#description'])) {
      $element['#description'] = ['description' => (is_array($element['#description'])) ? $element['#description'] : ['#markup' => $element['#description']]];
    }
    else {
      $element['#description'] = [];
    }

    // Add terms to #description.
    $element['#description']['terms'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $id . '--description',
        'class' => ['webform-terms-of-service-details', 'js-hide'],
      ],
    ];
    if (!empty($element['#terms_title'])) {
      $element['#description']['terms']['title'] = [
        '#type' => 'container',
        '#markup' => $element['#terms_title'],
        '#attributes' => [
          'class' => ['webform-terms-of-service-details--title'],
        ],
      ];
    }
    if (!empty($element['#terms_content'])) {
      $element['#description']['terms']['content'] = (is_array($element['#terms_content'])) ? $element['#terms_content'] : ['#markup' => $element['#terms_content']];
      $element['#description']['terms']['content'] += [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['webform-terms-of-service-details--content'],
        ],
      ];
    }

    // Add accessibility attributes to title and content.
    if ($element['#type'] === static::TERMS_SLIDEOUT) {

    }

    // Set type to data attribute.
    // @see Drupal.behaviors.webformTermsOfService.
    $element['#wrapper_attributes']['data-webform-terms-of-service-type'] = $element['#terms_type'];
    $element['#attached']['library'][] = 'webform/webform.element.terms_of_service';

    // Change #type to checkbox so that element is rendered correctly.
    $element['#type'] = 'checkbox';
    $element['#wrapper_attributes']['class'][] = 'form-type-webform-terms-of-service';
    $element['#wrapper_attributes']['class'][] = 'js-form-type-webform-terms-of-service';

    $element['#element_validate'][] = [get_called_class(), 'validateWebformTermsOfService'];

    return $element;
  }

  /**
   * Webform element validation handler for webform terms of service element.
   */
  public static function validateWebformTermsOfService(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = (bool) $form_state->getValue($element['#parents'], []);
    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

}
