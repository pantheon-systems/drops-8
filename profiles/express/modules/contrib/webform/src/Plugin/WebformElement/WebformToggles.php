<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'toggles' element.
 *
 * @WebformElement(
 *   id = "webform_toggles",
 *   label = @Translation("Toggles"),
 *   description = @Translation("Provides a form element for toggling multiple on/off states."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformToggles extends OptionsBase {

  use WebformToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'toggle_theme' => 'light',
      'toggle_size' => 'medium',
      'on_text' => '',
      'off_text' => '',
    ] + parent::getDefaultProperties();
    unset($properties['required'], $properties['required_message']);
    return $properties;

  }

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
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    parent::prepare($element, $webform_submission);
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
