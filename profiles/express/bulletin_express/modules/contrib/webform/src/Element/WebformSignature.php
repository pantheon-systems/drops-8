<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a webform element for entering a signature.
 *
 * @FormElement("webform_signature")
 */
class WebformSignature extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformSignature'],
      ],
      '#theme' => 'input__webform_signature',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Prepares a #type 'webform_signature' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderWebformSignature(array $element) {
    $element['#attributes']['type'] = 'hidden';
    Element::setAttributes($element, ['name', 'value']);
    static::setAttributes($element, ['js-webform-signature', 'form-webform-signature']);

    $build = [
      '#prefix' => '<div class="js-webform-signature-pad webform-signature-pad">',
      '#suffix' => '</div>',
    ];
    $build['reset'] = [
      '#type' => 'button',
      '#value' => t('Reset'),
    ];
    $build['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
    ];
    $element['#children'] = $build;

    $element['#attached']['library'][] = 'webform/webform.element.signature';
    return $element;
  }

}
