<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\Textfield;

/**
 * Provides a one-line text field with autocompletion webform element.
 *
 * @FormElement("webform_autocomplete")
 */
class WebformAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    $info = parent::getInfo();
    $info['#pre_render'][] = [$class, 'preRenderWebformAutocomplete'];
    return $info;
  }

  /**
   * Prepares a #type 'webform_autocomplete' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderWebformAutocomplete($element) {
    static::setAttributes($element, ['webform-autocomplete']);
    return $element;
  }

}
