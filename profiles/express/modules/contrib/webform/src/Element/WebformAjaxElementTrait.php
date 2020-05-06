<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Trait class for element Ajax support.
 *
 * @see \Drupal\webform_test_element\Plugin\WebformElement\WebformTestElementProperties
 */
trait WebformAjaxElementTrait {

  /**
   * Get Ajax element wrapper id.
   *
   * @param string $id
   *   A unique element id.
   *
   * @return string
   *   The element id suffixed with *-wrapper.
   */
  public function getAjaxElementWrapperId($id) {
    return $id . '-ajax-wrapper';
  }

  /**
   * Get Ajax element update name.
   *
   * @param string $id
   *   A unique element id.
   *
   * @return string
   *   The element update name suffixed with *-update.
   */
  public function getAjaxElementUpdateName($id) {
    return str_replace('-', '_', $id) . '_ajax_update';
  }

  /**
   * Get Ajax element update class.
   *
   * @param string $id
   *   A unique element id.
   *
   * @return string
   *   The element update classes prefixed with js-* and suffixed with *-update.
   */
  public function getAjaxElementUpdateClass($id) {
    return 'js-' . $id . '-ajax-update';
  }

  /**
   * Build an Ajax element.
   *
   * @param string $id
   *   The id used to create the Ajax wrapper and trigger.
   * @param array &$wrapper_element
   *   The element to be update via Ajax.
   * @param array &$trigger_element
   *   The element to trigger the Ajax update.
   * @param array|null &$update_element
   *   The element to append the hidden Ajax submit button.
   */
  public function buildAjaxElement($id, array &$wrapper_element, array &$trigger_element, array &$update_element = NULL) {
    static::buildAjaxElementWrapper($id, $wrapper_element);
    static::buildAjaxElementTrigger($id, $trigger_element);
    if ($update_element) {
      static::buildAjaxElementUpdate($id, $update_element);
    }
    else {
      static::buildAjaxElementUpdate($id, $wrapper_element);
    }
  }

  /**
   * Build an Ajax element's wrapper.
   *
   * @param string $id
   *   The id used to create the Ajax wrapper and trigger.
   * @param array &$element
   *   The element to be update via Ajax.
   */
  public function buildAjaxElementWrapper($id, array &$element) {
    $element['#prefix'] = '<div id="' . $this->getAjaxElementWrapperId($id) . '">';
    $element['#suffix'] = '</div>';
    $element['#attached']['library'][] = 'webform/webform.element.ajax';
    $element['#webform_ajax_element_type'] = 'wrapper';
    $element['#webform_ajax_element_id'] = $id;
  }

  /**
   * Build an Ajax element's trigger.
   *
   * @param string $id
   *   The id used to create the Ajax wrapper and trigger.
   * @param array &$element
   *   The element to trigger the Ajax update.
   */
  public function buildAjaxElementTrigger($id, array &$element) {
    $element['#attributes']['data-webform-trigger-submit'] = '.' . $this->getAjaxElementUpdateClass($id);
    $element['#webform_ajax_element_type'] = 'trigger';
    $element['#webform_ajax_element_id'] = $id;
  }

  /**
   * Build an Ajax element's update (submit) button.
   *
   * @param string $id
   *   The id used to create the Ajax wrapper and trigger.
   * @param array &$element
   *   The element to append the hidden Ajax submit button.
   */
  public function buildAjaxElementUpdate($id, array &$element) {
    $element['update'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      // Submit buttons with the same label must have a unique name.
      // @see https://www.drupal.org/project/drupal/issues/1342066
      '#name' => $this->getAjaxElementUpdateName($id),
      // Validate the element.
      '#validate' => [[get_called_class(), 'validateAjaxElementCallback']],
      // Submit the element.
      '#submit' => [[get_called_class(), 'submitAjaxElementCallback']],
      // Refresh the element.
      '#ajax' => [
        'callback' => [get_called_class(), 'updateAjaxElementCallback'],
        'wrapper' => $this->getAjaxElementWrapperId($id),
        'progress' => ['type' => 'fullscreen'],
      ],
      // Disable validation, hide the button, and add trigger update class.
      '#attributes' => [
        'formnovalidate' => 'formnovalidate',
        'class' => [
          'js-hide',
          $this->getAjaxElementUpdateClass($id),
        ],
      ],
      // Always render the update button.
      '#access' => TRUE,
      // Store a reference to the Ajax id.
      '#webform_ajax_element_type' => 'update',
      '#webform_ajax_element_id' => $id,
    ];
  }

  /**
   * Ajax element validate callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateAjaxElementCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if (empty($button['#limit_validation_errors'])) {
      $form_state->clearErrors();
    }
  }

  /**
   * Ajax element submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitAjaxElementCallback(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Ajax element update callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The properties element.
   */
  public static function updateAjaxElementCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $id = $button['#webform_ajax_element_id'];

    // Check if the immediate parent element is the Ajax wrapper.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    if (isset($element['#webform_ajax_element_id'])
      && $element['#webform_ajax_element_id'] === $id
      && isset($element['#webform_type'])
      && $element['#webform_type'] === 'wrapper') {
      return $element;
    }

    // Get the Ajax wrapper from the parent parent element.
    $parent_element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    return static::getAjaxElementWrapperRecursive($id, $parent_element);
  }

  /**
   * Get ajax element wrapper.
   *
   * @param string $id
   *   The id used to create the Ajax wrapper and trigger.
   * @param array $element
   *   An element or form containing the Ajax wrapper.
   *
   * @return array|null
   *   The Ajax wrapper element.
   */
  protected static function getAjaxElementWrapperRecursive($id, array $element) {
    if (isset($element['#webform_ajax_element_id'])
      && $element['#webform_ajax_element_id'] === $id
      && $element['#webform_ajax_element_type'] === 'wrapper') {
      return $element;
    }

    foreach (Element::children($element) as $key) {
      $ajax_element = static::getAjaxElementWrapperRecursive($id, $element[$key]);
      if ($ajax_element) {
        return $ajax_element;
      }
    }

    return NULL;
  }

}
