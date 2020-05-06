<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Element\WebformTermCheckboxes as TermCheckboxesElement;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;

/**
 * Provides a 'webform_term_checkboxes' element.
 *
 * @WebformElement(
 *   id = "webform_term_checkboxes",
 *   label = @Translation("Term checkboxes"),
 *   description = @Translation("Provides a form element to select a single or multiple terms displayed as hierarchical tree or as breadcrumbs using checkboxes."),
 *   category = @Translation("Entity reference elements"),
 *   dependencies = {
 *     "taxonomy",
 *   },
 * )
 */
class WebformTermCheckboxes extends Checkboxes implements WebformElementEntityReferenceInterface {

  use WebformTermReferenceTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'vocabulary' => '',
      'breadcrumb' => FALSE,
      'breadcrumb_delimiter' => ' › ',
      'tree_delimiter' => '&nbsp;&nbsp;&nbsp;',
      'scroll' => TRUE,
    ] + parent::defineDefaultProperties();

    unset(
      $properties['options'],
      $properties['options_randomize'],
      $properties['options_display']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    static::setOptions($element);
    return parent::getElementSelectorInputsOptions($element);
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptions(array &$element) {
    TermCheckboxesElement::setOptions($element);
  }

}
