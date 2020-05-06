<?php

namespace Drupal\webform_jqueryui_buttons\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\OptionsBase;
use Drupal\webform\Plugin\WebformElementOtherInterface;

/**
 * Provides a 'buttons_other' element.
 *
 * @WebformElement(
 *   id = "webform_buttons_other",
 *   label = @Translation("Buttons other"),
 *   description = @Translation("Provides a group of multiple buttons used for selecting a value, with the ability to enter a custom value."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformButtonsOther extends OptionsBase implements WebformElementOtherInterface {}
