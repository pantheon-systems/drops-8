<?php

namespace Drupal\webform_jqueryui_buttons\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a webform element for buttons.
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

    $element['#attached']['library'][] = 'webform_jqueryui_buttons/webform_jqueryui_buttons.element';

    return $element;
  }

}
