<?php

namespace Drupal\webform_toggles\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\OptionsBase;

/**
 * Provides a 'toggles' element.
 *
 * @WebformElement(
 *   id = "webform_toggles",
 *   label = @Translation("Toggles"),
 *   description = @Translation("Provides a form element for toggling multiple on/off states."),
 *   category = @Translation("Options elements"),
 *   deprecated = TRUE,
 *   deprecated_message = @Translation("The Toogles library is not being maintained and has major accessibility issues. It has been <a href=""https://www.drupal.org/project/webform/issues/2890861"">deprecated</a> and will be removed before Webform 8.x-5.0."),
 * )
 */
class WebformToggles extends OptionsBase {

  use WebformToggleTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'toggle_theme' => 'light',
      'toggle_size' => 'medium',
      'on_text' => '',
      'off_text' => '',
    ] + parent::defineDefaultProperties();
    unset(
      $properties['required'],
      $properties['required_message']
    );
    return $properties;

  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#options'];
    foreach ($selectors as &$text) {
      $text .= ' [' . $this->t('Toggle') . ']';
    }
    return $selectors;
  }

}
