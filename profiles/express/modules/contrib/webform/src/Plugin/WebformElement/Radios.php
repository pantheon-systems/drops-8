<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'radios' element.
 *
 * @WebformElement(
 *   id = "radios",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios",
 *   label = @Translation("Radios"),
 *   description = @Translation("Provides a form element for a set of radio buttons."),
 *   category = @Translation("Options elements"),
 * )
 */
class Radios extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Form display.
      'options_display' => 'one_column',
      'options_description_display' => 'description',
      'options__properties' => [],
      // Wrapper.
      'wrapper_type' => 'fieldset',
    ] + parent::defineDefaultProperties();
    unset(
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Issue #2856795: If radio buttons are required but not filled form is
    // nevertheless submitted.
    // Issue #2856315: Conditional Logic - Requiring Radios in a Fieldset.
    $element['#attached']['library'][] = 'webform/webform.element.radios';
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    // Unset empty string as default option to prevent '' === '0' issue.
    // @see \Drupal\Core\Render\Element\Radio::preRenderRadio
    if (isset($element['#default_value'])
      && $element['#default_value'] == ''
      && !isset($element['#options'][$element['#default_value']])) {
      unset($element['#default_value']);
    }
  }

}
