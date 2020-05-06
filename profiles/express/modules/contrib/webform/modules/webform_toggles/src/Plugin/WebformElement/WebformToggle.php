<?php

namespace Drupal\webform_toggles\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\Checkbox;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'toggle' element.
 *
 * @WebformElement(
 *   id = "webform_toggle",
 *   label = @Translation("Toggle"),
 *   description = @Translation("Provides a form element for toggling a single on/off state."),
 *   category = @Translation("Advanced elements"),
 *   deprecated = TRUE,
 *   deprecated_message = @Translation("The Toogles library is not being maintained and has major accessibility issues. It has been <a href=""https://www.drupal.org/project/webform/issues/2890861"">deprecated</a> and will be removed before Webform 8.x-5.0."),
 * )
 */
class WebformToggle extends Checkbox {

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
      'title_display' => 'after',
    ] + parent::defineDefaultProperties();
    unset($properties['required']);
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
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
