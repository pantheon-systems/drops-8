<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;

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
class WebformEntityRadios extends Radios implements WebformElementEntityReferenceInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

}
