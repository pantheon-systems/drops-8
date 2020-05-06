<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'tableselect' element.
 *
 * @WebformElement(
 *   id = "tableselect",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tableselect.php/class/Tableselect",
 *   label = @Translation("Table select"),
 *   description = @Translation("Provides a form element for a table with radios or checkboxes in left column."),
 *   category = @Translation("Options elements"),
 * )
 */
class TableSelect extends OptionsBase {

  use WebformTableTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Options settings.
      'multiple' => TRUE,
      'multiple_error' => '',
      // Table settings.
      'js_select' => TRUE,
    ] + parent::defineDefaultProperties();
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
  public function getElementSelectorOptions(array $element) {
    return $this->getTableSelectElementSelectorOptions($element);
  }

}
