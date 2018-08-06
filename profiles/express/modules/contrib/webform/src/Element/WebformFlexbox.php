<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;

/**
 * Provides a render element for webform flexbox.
 *
 * @FormElement("webform_flexbox")
 */
class WebformFlexbox extends Container {

  /**
   * {@inheritdoc}
   */
  public static function processContainer(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processContainer($element, $form_state, $complete_form);
    $element['#attributes']['class'][] = 'webform-flexbox';
    $element['#attributes']['class'][] = 'js-webform-flexbox';
    if (isset($element['#align_items'])) {
      $element['#attributes']['class'][] = 'webform-flexbox--' . $element['#align_items'];
    }
    $element['#attached']['library'][] = 'webform/webform.element.flexbox';
    return $element;
  }

}
