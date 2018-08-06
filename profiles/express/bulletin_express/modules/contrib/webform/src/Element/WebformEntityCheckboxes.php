<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a webform element for a entity checkboxes.
 *
 * @FormElement("webform_entity_checkboxes")
 */
class WebformEntityCheckboxes extends Checkboxes {

  use WebformEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    self::setOptions($element);
    return parent::processCheckboxes($element, $form_state, $complete_form);
  }

}
