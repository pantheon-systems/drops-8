<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Element\WebformTermSelect as TermSelectElement;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;

/**
 * Provides a 'webform_term_select' element.
 *
 * @WebformElement(
 *   id = "webform_term_select",
 *   label = @Translation("Term select"),
 *   description = @Translation("Provides a form element to select a single or multiple terms displayed as hierarchical tree or as breadcrumbs using a select menu."),
 *   category = @Translation("Entity reference elements"),
 *   dependencies = {
 *     "taxonomy",
 *   }
 * )
 */
class WebformTermSelect extends Select implements WebformElementEntityReferenceInterface {

  use WebformTermReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      'vocabulary' => '',
      'breadcrumb' => FALSE,
      'breadcrumb_delimiter' => ' › ',
      'tree_delimiter' => '-',
    ];

    unset($properties['options']);
    unset($properties['options_randomize']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptions(array &$element) {
    TermSelectElement::setOptions($element);
  }

}
