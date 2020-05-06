<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides an base composite webform element.
 */
abstract class WebformCompositeBase extends FormElement implements WebformCompositeInterface {

  use WebformCompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#access' => TRUE,
      '#process' => [
        [$class, 'processWebformComposite'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCompositeFormElement'],
      ],
      '#title_display' => 'invisible',
      '#required' => FALSE,
      '#flexbox' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $composite_elements = static::getCompositeElements($element);
    $composite_elements = WebformElementHelper::getFlattened($composite_elements);

    // Get default value for inputs.
    $default_value = [];
    foreach ($composite_elements as $composite_key => $composite_element) {
      $element_plugin = $element_manager->getElementInstance($composite_element);
      if ($element_plugin->isInput($composite_element)) {
        $default_value[$composite_key] = '';
      }
    }

    if ($input === FALSE) {
      if (empty($element['#default_value']) || !is_array($element['#default_value'])) {
        $element['#default_value'] = [];
      }
      return $element['#default_value'] + $default_value;
    }

    return (is_array($input)) ? $input + $default_value : $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCompositeFormElement($element) {
    $element['#theme_wrappers'][] = 'form_element';
    $element['#wrapper_attributes']['id'] = $element['#id'] . '--wrapper';
    $element['#wrapper_attributes']['class'][] = 'form-composite';

    $element['#attributes']['id'] = $element['#id'];

    // Add class name to wrapper attributes.
    $class_name = str_replace('_', '-', $element['#type']);
    static::setAttributes($element, ['js-' . $class_name, $class_name]);

    return $element;
  }

  /**
   * Processes a composite webform element.
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#initialize'])) {
      return $element;
    }
    $element['#initialize'] = TRUE;

    $element['#tree'] = TRUE;
    $composite_elements = static::initializeCompositeElements($element);
    static::processWebformCompositeElementsRecursive($element, $composite_elements, $form_state, $complete_form);
    $element += $composite_elements;

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformComposite']);

    if (!empty($element['#flexbox'])) {
      $element['#attached']['library'][] = 'webform/webform.element.flexbox';
    }

    return $element;
  }

  /**
   * Recursively processes a composite's elements.
   */
  public static function processWebformCompositeElementsRecursive(&$element, array &$composite_elements, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    // Get composite element required/options states from visible/hidden states.
    $composite_required_states = WebformElementHelper::getRequiredFromVisibleStates($element);

    foreach ($composite_elements as $composite_key => &$composite_element) {
      if (!Element::child($composite_key) || !is_array($composite_element)) {
        continue;
      }

      // Set parents.
      $composite_element['#parents'] = array_merge($element['#parents'], [$composite_key]);

      // If the element's #access is FALSE, apply it to all sub elements.
      if ($element['#access'] === FALSE) {
        $composite_element['#access'] = FALSE;
      }

      // Get element plugin and set inputs #default_value.
      $element_plugin = $element_manager->getElementInstance($composite_element);
      if ($element_plugin->isInput($composite_element)) {
        // Set #default_value for sub elements.
        if (isset($element['#value'][$composite_key])) {
          $composite_element['#default_value'] = $element['#value'][$composite_key];
        }
      }

      // Build the webform element.
      $element_manager->buildElement($composite_element, $complete_form, $form_state);

      // Custom validate required sub-element because they can be hidden
      // via #access or #states.
      // @see \Drupal\webform\Element\WebformCompositeBase::validateWebformComposite
      if ($composite_required_states && !empty($composite_element['#required'])) {
        unset($composite_element['#required']);
        $composite_element['#_required'] = TRUE;
        if (!isset($composite_element['#states'])) {
          $composite_element['#states'] = [];
        }
        $composite_element['#states'] += $composite_required_states;
      }

      static::processWebformCompositeElementsRecursive($element, $composite_element, $form_state, $complete_form);
    }
  }

  /**
   * Validates a composite element.
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    // IMPORTANT: Must get values from the $form_states since sub-elements
    // may call $form_state->setValueForElement() via their validation hook.
    // @see \Drupal\webform\Element\WebformEmailConfirm::validateWebformEmailConfirm
    // @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Only validate composite elements that are visible.
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access) {
      // Validate required composite elements.
      $composite_elements = static::getCompositeElements($element);
      $composite_elements = WebformElementHelper::getFlattened($composite_elements);
      foreach ($composite_elements as $composite_key => $composite_element) {
        $is_required = !empty($element[$composite_key]['#required']);
        $is_empty = (isset($value[$composite_key]) && $value[$composite_key] === '');
        if ($is_required && $is_empty) {
          WebformElementHelper::setRequiredError($element[$composite_key], $form_state);
        }
      }
    }

    // Clear empty composites value.
    if (empty(array_filter($value))) {
      $element['#value'] = NULL;
      $form_state->setValueForElement($element, NULL);
    }
  }

  /****************************************************************************/
  // Composite Elements.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function initializeCompositeElements(array &$element) {
    $composite_elements = static::getCompositeElements($element);
    static::initializeCompositeElementsRecursive($element, $composite_elements);
    return $composite_elements;
  }

  /**
   * Initialize a composite's elements recursively.
   *
   * @param array $element
   *   A render array for the current element.
   * @param array $composite_elements
   *   A render array containing a composite's elements.
   *
   * @throws \Exception
   *   Throws exception when unsupported element type is used with a composite
   *   element.
   */
  protected static function initializeCompositeElementsRecursive(array &$element, array &$composite_elements) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    foreach ($composite_elements as $composite_key => &$composite_element) {
      if (Element::property($composite_key)) {
        continue;
      }

      // Transfer '#{composite_key}_{property}' from main element to composite
      // element.
      foreach ($element as $property_key => $property_value) {
        if (strpos($property_key, '#' . $composite_key . '__') === 0) {
          $composite_property_key = str_replace('#' . $composite_key . '__', '#', $property_key);
          $composite_element[$composite_property_key] = $property_value;
        }
      }

      // Initialize composite sub-element.
      $element_plugin = $element_manager->getElementInstance($composite_element);

      // Make sure to remove any #options references from unsupported elements.
      // This prevents "An illegal choice has been detected." error.
      // @see FormValidator::performRequiredValidation()
      if (isset($composite_element['#options']) && !$element_plugin->hasProperty('options')) {
        unset($composite_element['#options']);
      }

      // Convert #placeholder to #empty_option for select elements.
      if (isset($composite_element['#placeholder']) && $element_plugin->hasProperty('empty_option')) {
        $composite_element['#empty_option'] = $composite_element['#placeholder'];
      }

      // Apply #select2, #choices, and #chosen to select elements.
      if (isset($composite_element['#type']) && strpos($composite_element['#type'], 'select') !== FALSE) {
        $select_properties = [
          '#select2' => '#select2',
          '#choices' => '#choices',
          '#chosen' => '#chosen',
        ];
        $composite_element += array_intersect_key($element, $select_properties);
      }

      if ($element_plugin->hasMultipleValues($composite_element)) {
        throw new \Exception('Multiple elements are not supported within composite elements.');
      }
      if ($element_plugin->isComposite()) {
        throw new \Exception('Nested composite elements are not supported within composite elements.');
      }

      $element_plugin->initialize($composite_element);

      static::initializeCompositeElementsRecursive($element, $composite_element);
    }
  }

}
