<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'checkbox' element.
 *
 * @WebformElement(
 *   id = "checkbox",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation("Provides a form element for a single checkbox."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Checkbox extends BooleanBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'title_display' => 'after',
      // iCheck settings.
      'icheck' => '',
    ] + parent::getDefaultProperties();
    unset($properties['unique'], $properties['unique_entity'], $properties['unique_user'], $properties['unique_error']);
    return $properties;
  }

}
