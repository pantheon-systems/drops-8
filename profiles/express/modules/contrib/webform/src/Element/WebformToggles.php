<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a webform element for toggles.
 *
 * @FormElement("webform_toggles")
 */
class WebformToggles extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return WebformToggle::getDefaultProperties() + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processCheckboxes($element, $form_state, $complete_form);

    // Convert checkboxes to toggle elements.
    foreach (Element::children($element) as $key) {
      $element[$key]['#type'] = 'webform_toggle';
      $element[$key] += array_intersect_key($element, WebformToggle::getDefaultProperties());
    }

    return $element;
  }

}
