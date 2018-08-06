<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a roles entity reference webform element.
 *
 * @FormElement("webform_roles")
 */
class WebformRoles extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#element_validate'] = [
      [$class, 'validateWebformRoles'],
    ];
    $info['#include_anonymous'] = TRUE;
    return $info;
  }

  /**
   * Processes a checkboxes webform element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $membersonly = (empty($element['#include_anonymous'])) ? TRUE : FALSE;
    $element['#options'] = array_map('\Drupal\Component\Utility\Html::escape', user_role_names($membersonly));
    $element['#attached']['library'][] = 'webform/webform.element.roles';
    $element['#attributes']['class'][] = 'js-webform-roles-role';
    return parent::processCheckboxes($element, $form_state, $complete_form);
  }

  /**
   * Webform element validation handler for webform_users elements.
   */
  public static function validateWebformRoles(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $form_state->getValue($element['#parents'], []);
    $form_state->setValueForElement($element, array_values(array_filter($value)));
  }

}
