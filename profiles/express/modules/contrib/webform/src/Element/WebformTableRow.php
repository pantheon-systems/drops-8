<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for webform table row.
 *
 * @FormElement("webform_table_row")
 */
class WebformTableRow extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#optional' => FALSE,
      '#process' => [
        [$class, 'processTableRow'],
      ],
      '#pre_render' => [],
    ];
  }

  /**
   * Processes a webfrom table row element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processTableRow(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attributes']['class'][] = 'webform-table-row';
    if (!empty($element['#states'])) {
      webform_process_states($element);
    }
    return $element;
  }

}
