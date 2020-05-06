<?php

/**
 * @file
 * Integrates third party settings on the Honeypot module's behalf.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Flag to indicate that a honeypot setting can be set.
 */
define('WEBFORM_HONEYPOT_NEUTRAL', -1);

/**
 * Flag to indicate that a honeypot setting is disabled for all webforms.
 */
define('WEBFORM_HONEYPOT_DISABLED_ALL', 0);

/**
 * Flag to indicate that a honeypot setting is enabled for all webforms.
 */
define('WEBFORM_HONEYPOT_ENABLED_ALL', 1);

/**
 * Flag to indicate that a honeypot setting is disabled for all webforms.
 */
define('WEBFORM_HONEYPOT_DISABLED_WEBFORM', 2);

/**
 * Flag to indicate that a honeypot setting is enabled for all webforms.
 */
define('WEBFORM_HONEYPOT_ENABLED_WEBFORM', 3);

/**
 * Alter webform third party settings webforms to include Honeypot configuration.
 *
 * @param array $form
 *   An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param bool $honeypot
 *   TRUE if honeypot protection is enabled.
 * @param int $honeypot_state
 *   Flag that determines if honeypot protection is enabled, disabled, or can be
 *   set.
 * @param bool $time_restriction
 *   TRUE if honeypot time restriction is enabled.
 * @param int $time_restriction_state
 *   Flag that determines if honeypot time restriction is enabled, disabled,
 *   or can be set.
 * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
 *   The label to displayed within the checkbox titles.
 */
function _webform_honeypot_form(array &$form, FormStateInterface $form_state, $honeypot, $honeypot_state, $time_restriction, $time_restriction_state, $label) {
  $t_args = [
    '%label' => $label,
    ':href_honeypot' => Url::fromRoute('honeypot.config')->toString(),
    ':href_webform' => Url::fromRoute('webform.config')->toString(),
  ];

  // Honeypot.
  $form['third_party_settings']['honeypot'] = [
    '#type' => 'details',
    '#title' => t('Honeypot'),
    '#open' => TRUE,
    '#description' => t('Mitigate SPAM webform submissions using the <a href=":href_honeypot">honeypot</a> method.', $t_args),
  ];
  $form['third_party_settings']['honeypot']['honeypot'] = [
    '#type' => 'checkbox',
    '#title' => t('Protect %label with Honeypot', $t_args),
    '#default_value' => $honeypot,
    '#return_value' => TRUE,
  ];

  if ($honeypot_state != WEBFORM_HONEYPOT_NEUTRAL) {
    $form['third_party_settings']['honeypot']['honeypot']['#attributes']['disabled'] = 'disabled';
    $form_state->set('honeypot_disabled', TRUE);
    if ($honeypot_state == WEBFORM_HONEYPOT_ENABLED_ALL) {
      $form['third_party_settings']['honeypot']['honeypot']['#default_value'] = 1;
      $form['third_party_settings']['honeypot']['honeypot']['#description'] = t('<a href=":href_honeypot">Honeypot protection</a> is enabled for all webforms.', $t_args);
    }
    elseif ($honeypot_state == WEBFORM_HONEYPOT_ENABLED_WEBFORM) {
      $form['third_party_settings']['honeypot']['honeypot']['#default_value'] = 1;
      $form['third_party_settings']['honeypot']['honeypot']['#description'] = t('<a href=":href_webform">Honeypot protection</a> is enabled for all webforms.', $t_args);
    }
  }

  // Time limit.
  $form['third_party_settings']['honeypot']['time_restriction'] = [
    '#type' => 'checkbox',
    '#title' => t('Add time restriction to %label', $t_args),
    '#description' => '',
    '#default_value' => $time_restriction,
    '#return_value' => TRUE,
  ];

  if ($time_restriction_state != WEBFORM_HONEYPOT_NEUTRAL) {
    $form['third_party_settings']['honeypot']['time_restriction']['#attributes']['disabled'] = 'disabled';
    $form_state->set('time_restriction_disabled', TRUE);
    if ($time_restriction_state == WEBFORM_HONEYPOT_DISABLED_ALL) {
      $form['third_party_settings']['honeypot']['time_restriction']['#default_value'] = 0;
      $form['third_party_settings']['honeypot']['time_restriction']['#description'] = t('<a href=":href_honeypot">Time limit</a> is disabled for all webforms.', $t_args);
    }
    elseif ($time_restriction_state == WEBFORM_HONEYPOT_ENABLED_WEBFORM) {
      $form['third_party_settings']['honeypot']['time_restriction']['#default_value'] = 1;
      $form['third_party_settings']['honeypot']['time_restriction']['#description'] = t('<a href=":href_webform">Time limit</a> is enabled for all webforms.', $t_args);
    }
  }

  $form['third_party_settings']['honeypot']['time_restriction']['#description'] = ($form['third_party_settings']['honeypot']['time_restriction']['#description'] ? '<br/>' : '')
    . '<strong>' . t('Page caching will be disabled when a time restriction is applied to a webform.') . '</strong>';

  $form['#validate'][] = '_webform_honeypot_form_validate';

}

/**
 * Validate callback; Checks if honeypot or time_restriction is disabled and removes them from the third party settings values.
 */
function _webform_honeypot_form_validate(&$form, FormStateInterface $form_state) {
  $third_party_settings = $form_state->getValue('third_party_settings');
  if ($form_state->get('honeypot_disabled') || empty($third_party_settings['honeypot']['honeypot'])) {
    unset($third_party_settings['honeypot']['honeypot']);
  }
  if ($form_state->get('time_restriction_disabled') || empty($third_party_settings['honeypot']['time_restriction'])) {
    unset($third_party_settings['honeypot']['time_restriction']);
  }
  if (empty($third_party_settings['honeypot'])) {
    unset($third_party_settings['honeypot']);
  }
  $form_state->setValue('third_party_settings', $third_party_settings);
}

/**
 * Implements hook_webform_admin_third_party_settings_form_alter().
 */
function honeypot_webform_admin_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  $honeypot = $third_party_settings_manager->getThirdPartySetting('honeypot', 'honeypot');
  $honeypot_state = \Drupal::config('honeypot.settings')->get('protect_all_forms') ? WEBFORM_HONEYPOT_ENABLED_ALL : WEBFORM_HONEYPOT_NEUTRAL;

  $time_restriction = $third_party_settings_manager->getThirdPartySetting('honeypot', 'time_restriction');
  $time_restriction_state = (\Drupal::config('honeypot.settings')->get('time_limit') == 0) ? WEBFORM_HONEYPOT_DISABLED_ALL : WEBFORM_HONEYPOT_NEUTRAL;

  _webform_honeypot_form(
    $form,
    $form_state,
    $honeypot,
    $honeypot_state,
    $time_restriction,
    $time_restriction_state,
    t('all webforms')
  );
}

/**
 * Implements hook_webform_third_party_settings_form_alter().
 */
function honeypot_webform_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $form_state->getFormObject()->getEntity();

  $honeypot = $webform->getThirdPartySetting('honeypot', 'honeypot');
  if (\Drupal::config('honeypot.settings')->get('protect_all_forms')) {
    $honeypot_state = WEBFORM_HONEYPOT_ENABLED_ALL;
  }
  elseif ($third_party_settings_manager->getThirdPartySetting('honeypot', 'honeypot')) {
    $honeypot_state = WEBFORM_HONEYPOT_ENABLED_WEBFORM;
  }
  else {
    $honeypot_state = WEBFORM_HONEYPOT_NEUTRAL;
  }

  $time_restriction = $webform->getThirdPartySetting('honeypot', 'time_restriction');
  if (\Drupal::config('honeypot.settings')->get('time_limit') == 0) {
    $time_restriction_state = WEBFORM_HONEYPOT_DISABLED_ALL;
  }
  elseif ($third_party_settings_manager->getThirdPartySetting('honeypot', 'time_restriction')) {
    $time_restriction_state = WEBFORM_HONEYPOT_ENABLED_WEBFORM;
  }
  else {
    $time_restriction_state = WEBFORM_HONEYPOT_NEUTRAL;
  }

  _webform_honeypot_form(
    $form,
    $form_state,
    $honeypot,
    $honeypot_state,
    $time_restriction,
    $time_restriction_state,
    t('@label webform', ['@label' => $webform->label()])
  );
}

/**
 * Implements hook_webform_submission_form_alter().
 */
function honeypot_webform_submission_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Only add a Honeypot when a webform is initially load.
  // After a webform is submitted, via a multi-step webform and/or saving a draft,
  // we can skip adding a Honeypot.
  if ($form_state->isSubmitted()) {
    return;
  }

  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $form_state->getFormObject()->getEntity();
  $webform = $webform_submission->getWebform();

  $options = [];

  $honeypot = $third_party_settings_manager->getThirdPartySetting('honeypot', 'honeypot') ?:
    $webform->getThirdPartySetting('honeypot', 'honeypot');
  if ($honeypot) {
    $options[] = 'honeypot';
  }

  $time_restriction = $third_party_settings_manager->getThirdPartySetting('honeypot', 'time_restriction') ?:
    $webform->getThirdPartySetting('honeypot', 'time_restriction');
  if ($time_restriction) {
    $options[] = 'time_restriction';
  }

  if ($options) {
    honeypot_add_form_protection($form, $form_state, $options);
  }
}
