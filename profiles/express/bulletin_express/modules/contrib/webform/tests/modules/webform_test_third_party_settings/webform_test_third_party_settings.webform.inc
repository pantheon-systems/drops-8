<?php

/**
 * @file
 * Integrates third party settings for the Webform module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function webform_test_third_party_settings_form_webform_admin_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  $form['third_party_settings']['webform_test_third_party_settings'] = [
    '#type' => 'details',
    '#title' => t('Webform test third party admin settings'),
    '#open' => TRUE,
  ];
  $form['third_party_settings']['webform_test_third_party_settings']['message'] = [
    '#type' => 'textfield',
    '#title' => t('Display the below message on every webform'),
    '#default_value' => $third_party_settings_manager->getThirdPartySetting('webform_test_third_party_settings', 'message'),
  ];
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function webform_test_third_party_settings_form_webform_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $form_state->getFormObject()->getEntity();

  $form['third_party_settings']['webform_test_third_party_settings'] = [
    '#type' => 'details',
    '#title' => t('Webform test third party settings'),
    '#open' => TRUE,
  ];
  $form['third_party_settings']['webform_test_third_party_settings']['message'] = [
    '#type' => 'textfield',
    '#title' => t('Display the below message'),
    '#default_value' => $webform->getThirdPartySetting('webform_test_third_party_settings', 'message'),
  ];
}

/**
 * Implements hook_webform_submission_form_alter().
 */
function webform_test_third_party_settings_webform_submission_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (!empty($_POST)) {
    return;
  }

  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $form_state->getFormObject()->getEntity();
  $webform = $webform_submission->getWebform();

  // Get message from the webform settings or the webform admin settings.
  $message = $webform->getThirdPartySetting('webform_test_third_party_settings', 'message') ?:
    $third_party_settings_manager->getThirdPartySetting('webform_test_third_party_settings', 'message');

  // If a message is set, display it.
  if ($message) {
    drupal_set_message($message);
  }
}
