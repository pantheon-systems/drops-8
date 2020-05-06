<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;
use Drupal\webform\Utility\WebformElementHelper;

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
    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\webform_submission $webform_submission */
    $webform_submission = $form_object->getEntity();

    $prefix = ($element['#webform_key']) ? 'edit-' . $element['#webform_key'] . '-' : '';

    // Add class names only if form['actions']['#type'] is set to 'actions'.
    if (WebformElementHelper::isType($complete_form['actions'], 'actions')) {
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

      // Get settings name.
      // The 'submit' button is used for creating and updating submissions.
      $is_update_button = ($button_name === 'submit' && !($webform_submission->isNew() || $webform_submission->isDraft()));
      $settings_name = ($is_update_button) ? 'update' : $button_name;

      // Set unique id for each button.
      if ($prefix) {
        $element[$button_name]['#id'] = Html::getUniqueId("$prefix$button_name");
      }

      // Hide buttons using #access.
      if (!empty($element['#' . $settings_name . '_hide'])) {
        $element[$button_name]['#access'] = FALSE;
      }

      // Apply custom label.
      $has_custom_label = !empty($element[$button_name]['#webform_actions_button_custom']);
      if (!empty($element['#' . $settings_name . '__label']) && !$has_custom_label) {
        $element[$button_name]['#value'] = $element['#' . $settings_name . '__label'];
      }

      // Apply custom name when needed for multiple submit buttons with
      // the same label.
      // @see https://www.drupal.org/project/webform/issues/3069240
      if (!empty($element['#' . $settings_name . '__name'])) {
        $element[$button_name]['#name'] = $element['#' . $settings_name . '__name'];
      }

      // Apply attributes (class, style, properties).
      if (!empty($element['#' . $settings_name . '__attributes'])) {
        foreach ($element['#' . $settings_name . '__attributes'] as $attribute_name => $attribute_value) {
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

    // Hide form actions only if the element is accessible.
    // This prevents form from having no actions.
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access) {
      $complete_form['actions']['#access'] = FALSE;
    }

    // Hide actions element if no buttons are visible (i.e. #access = FALSE).
    if (!$has_visible_button) {
      $element['#access'] = FALSE;
    }

    return $element;
  }

}
