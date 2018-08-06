<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase as WebformManagedFileBasePlugin;

/**
 * Provides an base composite webform element.
 */
abstract class WebformCompositeBase extends FormElement {

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
        [$class, 'preRenderCompositeFormElement'],
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
    $default_value = [];

    $composite_elements = static::getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      if (isset($composite_element['#type']) && $composite_element['#type'] != 'label') {
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
   * Get a renderable array of webform elements.
   *
   * @return array
   *   A renderable array of webform elements, containing the base properties
   *   for the composite's webform elements.
   */
  public static function getCompositeElements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCompositeFormElement($element) {
    $element['#theme_wrappers'][] = 'form_element';
    $element['#wrapper_attributes']['id'] = $element['#id'] . '--wrapper';
    $element['#wrapper_attributes']['class'][] = 'form-composite';

    // Add class name to wrapper attributes.
    $class_name = str_replace('_', '-', $element['#type']);
    static::setAttributes($element, ['js-' . $class_name, $class_name]);

    return $element;
  }

  /**
   * Initialize a composite elements.
   */
  public static function initializeCompositeElements(&$element) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $composite_elements = static::getCompositeElements();
    foreach ($composite_elements as $composite_key => &$composite_element) {
      // Transfer '#{composite_key}_{property}' from main element to composite
      // element.
      foreach ($element as $property_key => $property_value) {
        if (strpos($property_key, '#' . $composite_key . '__') === 0) {
          $composite_property_key = str_replace('#' . $composite_key . '__', '#', $property_key);
          $composite_element[$composite_property_key] = $property_value;
        }
      }

      // Make sure to remove any #options reference on textfields
      // To prevnnt "An illegal choice has been detected." error.
      // @see FormValidator::performRequiredValidation()
      if ($composite_element['#type'] == 'textfield') {
        unset($composite_element['#options']);
      }

      // Initialize, prepare, and populate composite sub-element.
      $element_plugin = $element_manager->getElementInstance($composite_element);

      // Note: File uploads are not supported because uploaded file
      // destination save and delete callbacks are not setup.
      // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::postSave
      // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::postDelete
      if ($element_plugin instanceof WebformManagedFileBasePlugin) {
        throw new \Exception('File upload element is not supported within composite elements.');
      }
      if ($element_plugin->hasMultipleValues($composite_element)) {
        throw new \Exception('Multiple elements are not supported within composite elements.');
      }
      if ($element_plugin->isComposite()) {
        throw new \Exception('Nested composite elements are not supported within composite elements.');
      }

      $element_plugin->initialize($composite_element);
    }

    return $composite_elements;
  }

  /**
   * Processes a composite webform element.
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#initialize'])) {
      return $element;
    }
    $element['#initialize'] = TRUE;

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element['#tree'] = TRUE;
    $composite_elements = static::initializeCompositeElements($element);
    foreach ($composite_elements as $composite_key => &$composite_element) {
      // Set #default_value for sub elements.
      if (isset($element['#value'][$composite_key])) {
        $composite_element['#default_value'] = $element['#value'][$composite_key];
      }

      // If the element's #access is FALSE, apply it to all sub elements.
      if ($element['#access'] === FALSE) {
        $composite_element['#access'] = FALSE;
      }

      // Initialize, prepare, and populate composite sub-element.
      $element_plugin = $element_manager->getElementInstance($composite_element);
      $element_plugin->prepare($composite_element);
      $element_plugin->finalize($composite_element);
      $element_plugin->setDefaultValue($composite_element);
    }

    $element += $composite_elements;
    $element['#element_validate'] = [[get_called_class(), 'validateWebformComposite']];

    if (!empty($element['#flexbox'])) {
      $element['#attached']['library'][] = 'webform/webform.element.flexbox';
    }

    return $element;
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

    /************************************************************************/
    // @todo Remove below code once WebformElement integration is completed.
    /************************************************************************/

    // Only validate composite elements that are visible.
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access) {
      // Validate required composite elements.
      $composite_elements = static::getCompositeElements();
      foreach ($composite_elements as $composite_key => $composite_element) {
        if (!empty($element[$composite_key]['#required']) && $value[$composite_key] == '') {
          if (isset($element[$composite_key]['#title'])) {
            $form_state->setError($element[$composite_key], t('@name field is required.', ['@name' => $element[$composite_key]['#title']]));
          }
        }
      }
    }

    // Clear empty composites value.
    if (empty(array_filter($value))) {
      $form_state->setValueForElement($element, NULL);
    }
  }

}
