<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Plugin\WebformElement\TableSelect;

/**
 * Provides a base webform element for webform excluded elements and columns.
 *
 * This element is just intended to capture all the business logic around
 * selecting excluded webform elements which is used by the
 * EmailWebformHandler and the WebformResultsExportForm webforms.
 */
abstract class WebformExcludedBase extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformExcluded'],
      ],
      '#webform_id' => NULL,
      '#theme_wrappers' => ['form_element'],
      '#default_value' => [],
    ];
  }

  /**
   * Processes a webform elements webform element.
   */
  public static function processWebformExcluded(&$element, FormStateInterface $form_state, &$complete_form) {
    $options = static::getWebformExcludedOptions($element);

    $default_value = array_diff(array_keys($options), array_keys($element['#default_value'] ?: []));
    $element['#tree'] = TRUE;

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformExcluded']);

    $element['tableselect'] = [
      '#type' => 'tableselect',
      '#header' => static::getWebformExcludedHeader(),
      '#options' => $options,
      '#js_select' => TRUE,
      '#empty' => t('No elements are available.'),
      '#default_value' => array_combine($default_value, $default_value),
    ];
    TableSelect::setProcessTableSelectCallback($element['tableselect']);

    if (isset($element['#parents'])) {
      $element['tableselect']['#parents'] = array_merge($element['#parents'], ['tableselect']);
    }

    // Build tableselect element with selected properties.
    $properties = [
      '#title',
      '#title_display',
      '#description',
      '#description_display',
      '#ajax',
      '#states',
    ];
    $element['tableselect'] += array_intersect_key($element, array_combine($properties, $properties));
    return $element;
  }

  /**
   * Validates a tablelselect element.
   */
  public static function validateWebformExcluded(array &$element, FormStateInterface $form_state, &$complete_form) {
    $value = array_filter($element['tableselect']['#value']);

    // Converted value to excluded elements.
    $options = array_keys($element['tableselect']['#options']);
    $excluded = array_diff($options, $value);

    // Unset tableselect and set the element's value to excluded.
    $form_state->setValueForElement($element['tableselect'], NULL);

    $value = array_combine($excluded, $excluded);
    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Get header for the excluded tableselect element.
   *
   * @return array
   *   An array container the header for the excluded tableselect element.
   */
  public static function getWebformExcludedHeader() {
    return [];
  }

  /**
   * Get options for excluded tableselect element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic element element.
   *
   * @return array
   *   An array of options containing title, name, and type of items for a
   *   tableselect element.
   */
  public static function getWebformExcludedOptions(array $element) {
    return [];
  }

}
