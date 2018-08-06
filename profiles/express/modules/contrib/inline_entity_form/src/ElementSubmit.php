<?php

namespace Drupal\inline_entity_form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides #ief_element_submit, the submit version of #element_validate.
 *
 * #ief_element_submit callbacks are invoked by a #submit callback added
 * to the form's main submit button.
 */
class ElementSubmit {

  /**
   * Attaches the #ief_element_submit functionality to the given form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function attach(&$form, FormStateInterface $form_state) {
    // attach() is called for each IEF form element, but the callbacks only
    // need to be added once per form build.
    if (isset($form['#ief_element_submit_attached'])) {
      return;
    }
    $form['#ief_element_submit_attached'] = TRUE;

    // Entity form actions.
    foreach (['submit', 'publish', 'unpublish'] as $action) {
      if (!empty($form['actions'][$action])) {
        self::addCallback($form['actions'][$action], $form);
      }
    }
    // Generic submit button.
    if (!empty($form['submit'])) {
      self::addCallback($form['submit'], $form);
    }
  }

  /**
   * Adds the trigger callback to the given submit element.
   *
   * @param array $element
   *   The submit element.
   * @param array $complete_form
   *   The complete form.
   */
  public static function addCallback(&$element, $complete_form) {
    if (empty($element['#submit'])) {
      // Drupal runs either the button-level callbacks or the form-level ones.
      // Having no button-level callbacks indicates that the form has relied
      // on form-level callbacks, which now need to be transferred.
      $element['#submit'] = $complete_form['#submit'];
    }

    $element['#submit'] = array_merge([[get_called_class(), 'trigger']], $element['#submit']);
    // Used to distinguish between an inline form submit and main form submit.
    $element['#ief_submit_trigger']  = TRUE;
    $element['#ief_submit_trigger_all'] = TRUE;
  }

  /**
   * Button #submit callback: Triggers submission of element forms.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function trigger($form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    if (!empty($triggered_element['#ief_submit_trigger_all'])) {
      // The parent form was submitted, process all IEFs and their children.
      static::doSubmit($form, $form_state);
    }
    else {
      // A specific element was submitted, process it and all of its children.
      $array_parents = $triggered_element['#array_parents'];
      $array_parents = array_slice($array_parents, 0, -2);
      $element = NestedArray::getValue($form, $array_parents);
      static::doSubmit($element, $form_state);
    }
  }

  /**
   * Submits elements by calling their #ief_element_submit callbacks.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function doSubmit($element, FormStateInterface $form_state) {
    // Recurse through all children.
    foreach (Element::children($element) as $key) {
      if (!empty($element[$key])) {
        static::doSubmit($element[$key], $form_state);
      }
    }

    // If there are callbacks on this level, run them.
    if (!empty($element['#ief_element_submit'])) {
      foreach ($element['#ief_element_submit'] as $callback) {
        call_user_func_array($callback, [&$element, &$form_state]);
      }
    }
  }

}
