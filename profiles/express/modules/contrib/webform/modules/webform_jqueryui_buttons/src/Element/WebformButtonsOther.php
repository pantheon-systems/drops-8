<?php

namespace Drupal\webform_jqueryui_buttons\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformOtherBase;

/**
 * Provides a webform element for buttons with an other option.
 *
 * @FormElement("webform_buttons_other")
 */
class WebformButtonsOther extends WebformOtherBase {

  /**
   * {@inheritdoc}
   */
  protected static $type = 'webform_buttons';

  /**
   * Processes an 'other' element.
   *
   * See select list webform element for select list properties.
   *
   * @see \Drupal\Core\Render\Element\Select
   */
  public static function processWebformOther(&$element, FormStateInterface $form_state, &$complete_form) {
    // Attach element buttons before other handler.
    $element['#attached']['library'][] = 'webform/webform.element.buttons';

    $element['#wrapper_attributes']['class'][] = "js-webform-buttons";
    $element['#wrapper_attributes']['class'][] = "webform-buttons";

    $element = parent::processWebformOther($element, $form_state, $complete_form);
    return $element;
  }

}
