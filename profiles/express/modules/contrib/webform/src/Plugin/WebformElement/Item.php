<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'item' element.
 *
 * @WebformElement(
 *   id = "item",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Item.php/class/Item",
 *   label = @Translation("Item"),
 *   description = @Translation("Provides a display-only form element with an optional title and description."),
 *   category = @Translation("Containers"),
 * )
 */
class Item extends WebformMarkup {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'title' => '',
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'help_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      // Form validation.
      'required' => FALSE,
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateItem'];
  }

  /**
   * Form API callback. Removes ignored element for $form_state values.
   */
  public static function validateItem(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $name = $element['#name'];
    $form_state->unsetValue($name);
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#markup' => '{markup}',
      '#field_prefix' => '{field_prefix}',
      '#field_suffix' => '{field_suffix}',
    ];
  }

}
