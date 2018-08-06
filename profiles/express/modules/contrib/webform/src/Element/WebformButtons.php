<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a webform element for buttons with an other option.
 *
 * @FormElement("webform_buttons")
 */
class WebformButtons extends Radios {

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processRadios($element, $form_state, $complete_form);

    $element['#attributes']['class'][] = 'js-webform-buttons';
    $element['#attributes']['class'][] = 'webform-buttons';
    $element['#options_display'] = 'side_by_side';

    if (floatval(\Drupal::VERSION) < 8.4) {
      // Buttonset is deprecated jQueryUI 1.12
      // https://api.jqueryui.com/buttonset/
      $element['#attached']['library'][] = 'webform/webform.element.buttons.buttonset';
    }
    else {
      $element['#attached']['library'][] = 'webform/webform.element.buttons.checkboxradio';
    }

    return $element;
  }

}
