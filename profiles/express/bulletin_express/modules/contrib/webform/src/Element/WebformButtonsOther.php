<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;

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
    $element = parent::processWebformOther($element, $form_state, $complete_form);
    return $element;
  }

}
