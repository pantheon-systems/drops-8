<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementEntityOptionsInterface;

/**
 * Provides a 'webform_entity_select' element.
 *
 * @WebformElement(
 *   id = "webform_entity_select",
 *   label = @Translation("Entity select"),
 *   description = @Translation("Provides a form element to select a single or multiple entity references using a select menu."),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class WebformEntitySelect extends Select implements WebformElementEntityOptionsInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

}
