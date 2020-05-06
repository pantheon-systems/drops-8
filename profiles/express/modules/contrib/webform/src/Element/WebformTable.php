<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a render element for webform table.
 *
 * @FormElement("webform_table")
 */
class WebformTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processTable(&$element, FormStateInterface $form_state, &$complete_form) {
    // @see \Drupal\Core\Render\Element\Table::getInfo
    $element['#input'] = TRUE;
    $element['#tableselect'] = FALSE;
    $element['#tabledrag'] = FALSE;
    $element['#tree'] = FALSE;

    // Add .webform-table class to the table element.
    $element['#attributes']['class'][] = 'webform-table';

    // Remove 'for' attribute from form wrapper's label.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    // Add webform table CSS.
    $element['#attached']['library'][] = 'webform/webform.element.table';

    return $element;
  }

}
