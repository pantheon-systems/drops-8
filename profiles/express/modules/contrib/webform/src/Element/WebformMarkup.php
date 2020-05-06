<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for webform markup.
 *
 * @FormElement("webform_markup")
 */
class WebformMarkup extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderWebformMarkup'],
      ],
    ];
  }

  /**
   * Create webform markup for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with webform html markup.
   */
  public static function preRenderWebformMarkup(array $element) {
    // Make sure that #markup is defined.
    if (!isset($element['#markup'])) {
      return $element;
    }

    // Replace #markup with renderable webform HTML editor markup.
    $element['markup'] = WebformHtmlEditor::checkMarkup($element['#markup'], ['tidy' => FALSE]);
    unset($element['#markup']);

    // Must set wrapper id attribute since we are no longer including #markup.
    // @see template_preprocess_form_element()
    if (isset($element['#theme_wrappers']) && !empty($element['#id'])) {
      $element['#wrapper_attributes']['id'] = $element['#id'];
    }

    // Sent #name property which is used by form-item-* classes.
    if (!isset($element['#name']) && isset($element['#webform_key'])) {
      $element['#name'] = $element['#webform_key'];
    }

    return $element;
  }

}
