<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'toggle' element.
 *
 * @WebformElement(
 *   id = "webform_toggle",
 *   label = @Translation("Toggle"),
 *   description = @Translation("Provides a form element for toggling a single on/off state."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformToggle extends Checkbox {

  use WebformToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      'toggle_theme' => 'light',
      'toggle_size' => 'medium',
      'on_text' => '',
      'off_text' => '',
    ];
    $properties['title_display'] = 'after';
    unset($properties['icheck'], $properties['required']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'value':
        $on_text = (!empty($element['#on_text'])) ? $element['#on_text'] : $this->t('Yes');
        $off_text = (!empty($element['#off_text'])) ? $element['#off_text'] : $this->t('No');
        return ($value) ? $on_text : $off_text;

      case 'raw':
      default:
        return ($value) ? 1 : 0;
    }
  }

}
