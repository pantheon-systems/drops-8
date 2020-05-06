<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementEntityOptionsInterface;

/**
 * Provides a 'webform_entity_radios' element.
 *
 * @WebformElement(
 *   id = "webform_entity_radios",
 *   label = @Translation("Entity radios"),
 *   description = @Translation("Provides a form element to select a single entity reference using radio buttons."),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class WebformEntityRadios extends Radios implements WebformElementEntityOptionsInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

}
