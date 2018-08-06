<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a webform element for a entity radios.
 *
 * @FormElement("webform_entity_radios")
 */
class WebformEntityRadios extends Radios {

  use WebformEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    static::setOptions($element);
    return parent::processRadios($element, $form_state, $complete_form);
  }

}
