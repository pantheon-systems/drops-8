<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'label' element.
 *
 * @WebformElement(
 *   id = "label",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Label.php/class/Label",
 *   label = @Translation("Label"),
 *   description = @Translation("Provides an element for displaying the label for a form element."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class Label extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'title' => '',
      // General settings.
      'description' => '',
      // Form validation.
      'required' => FALSE,
      // Attributes.
      'attributes' => [],
    ] + $this->defineDefaultBaseProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [];
  }

}
