<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'fieldset' element.
 *
 * @WebformElement(
 *   id = "fieldset",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Fieldset.php/class/Fieldset",
 *   label = @Translation("Fieldset"),
 *   description = @Translation("Provides an element for a group of form elements."),
 *   category = @Translation("Containers"),
 * )
 */
class Fieldset extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'help_display' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Title.
      'title_display' => '',
      'description_display' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'fieldset';
  }

}
