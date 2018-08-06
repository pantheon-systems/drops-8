<?php

use Drupal\Core\Form\FormStateInterface;

/*
 * @file
 * Helper functions for the fieldgroup module.
 */

/**
 * Get the default formatter settings for a given formatter and context.
 */
function _field_group_get_default_formatter_settings($format_type, $context) {
  $manager = Drupal::service('plugin.manager.field_group.formatters');
  return $manager->getDefaultSettings($format_type, $context);
}

/**
 * Return an array of field_group_formatter options.
 */
function field_group_field_formatter_options($type) {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $options = array();

    $manager = Drupal::service('plugin.manager.field_group.formatters');
    $formatters = $manager->getDefinitions();

    foreach ($formatters as $formatter) {
      if (in_array($type, $formatter['supported_contexts'])) {
        $options[$formatter['id']] = $formatter['label'];
      }
    }
  }

  return $options;
}

/**
 * Validate the entered css class from the submitted format settings.
 * @param Array $element The validated element
 * @param FormStateInterface $form_state The state of the form.
 */
function field_group_validate_css_class($element, FormStateInterface $form_state) {
  $form_state_values = $form_state->getValues();
  $plugin_name = $form_state->get('plugin_settings_edit');
  if (!empty($form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['classes']) && !preg_match('!^[A-Za-z0-9-_ ]+$!', $form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['classes'])) {
    Drupal::formBuilder()->setError($element, $form_state, t('The css class must include only letters, numbers, underscores and dashes.'));
  }
}

/**
 * Validate the entered id attribute from the submitted format settings.
 * @param Array $element The validated element
 * @param FormStateInterface $form_state The state of the form.
 */
function field_group_validate_id($element, FormStateInterface $form_state) {
  $form_state_values = $form_state->getValues();
  $plugin_name = $form_state->get('plugin_settings_edit');
  if (!empty($form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['id']) && !preg_match('!^[A-Za-z0-9-_]+$!', $form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['id'])) {
    Drupal::formBuilder()->setError($element, $form_state, t('The id must include only letters, numbers, underscores and dashes.'));
  }
}