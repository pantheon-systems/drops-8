<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;

/**
 * Provides a wrapper element to group one or more Webform buttons in a form.
 *
 * @RenderElement("webform_actions")
 *
 * @see \Drupal\Core\Render\Element\Actions
 */
class WebformActions extends Container {

  public static $buttons = [
    'submit',
    'reset',
    'draft',
    'wizard_prev',
    'wizard_next',
    'preview_prev',
    'preview_next',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processWebformActions'],
        [$class, 'processContainer'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes a form actions container element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   form actions container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformActions(&$element, FormStateInterface $form_state, &$complete_form) {
    $prefix = ($element['#webform_key']) ? 'edit-' . $element['#webform_key'] . '-' : '';

    // Add class names only if form['actions']['#type'] is set to 'actions'.
    if (isset($complete_form['actions']['#type']) && $complete_form['actions']['#type'] == 'actions') {
      $element['#attributes']['class'][] = 'form-actions';
      $element['#attributes']['class'][] = 'webform-actions';
    }

    // Copy the form's actions to this element.
    $element += $complete_form['actions'];

    // Track if buttons are visible.
    $has_visible_button = FALSE;
    foreach (static::$buttons as $button_name) {
      // Make sure the button exists.
      if (!isset($element[$button_name])) {
        continue;
      }

      // Set unique id for each button.
      if ($prefix) {
        $element[$button_name]['#id'] = Html::getUniqueId("$prefix$button_name");
      }

      // Hide buttons using #access.
      if (!empty($element['#' . $button_name .'_hide'])) {
        $element[$button_name]['#access'] = FALSE;
      }

      // Apply custom label.
      if (!empty($element['#' . $button_name .'__label']) && empty($element[$button_name]['#webform_actions_button_custom'])) {
        $element[$button_name]['#value'] = $element['#' . $button_name .'__label'];
      }

      // Apply attributes (class, style, properties).
      if (!empty($element['#' . $button_name .'__attributes'])) {
        foreach ($element['#' . $button_name .'__attributes'] as $attribute_name => $attribute_value) {
          if ($attribute_name == 'class') {
            // Merge class names.
            $element[$button_name]['#attributes']['class'] = array_merge($element[$button_name]['#attributes']['class'], $attribute_value);
          }
          else {
            $element[$button_name]['#attributes'][$attribute_name] = $attribute_value;
          }
        };
      }

      if (!isset($element[$button_name]['#access']) || $element[$button_name]['#access'] === TRUE) {
        $has_visible_button = TRUE;
      }
    }

    // Hide actions element if no buttons are visible (i.e. #access = FALSE).
    if (!$has_visible_button) {
      $element['#access'] = FALSE;
    }

    // Hide form actions.
    $complete_form['actions']['#access'] = FALSE;

    return $element;
  }

}
