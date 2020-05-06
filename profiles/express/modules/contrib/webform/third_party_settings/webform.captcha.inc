<?php

/**
 * @file
 * Integrates third party settings on the CAPTCHA module's behalf.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Implements hook_webform_admin_third_party_settings_form_alter().
 */
function captcha_webform_admin_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
  $replace_administration_mode = $third_party_settings_manager->getThirdPartySetting('captcha', 'replace_administration_mode');

  $t_args = [
    ':href' => Url::fromRoute('captcha_settings')->toString(),
    '@from' => t('Place a CAPTCHA here for untrusted users.'),
    '@to' => t('Add CAPTCHA element to this webform for untrusted users.'),
  ];

  $form['third_party_settings']['captcha'] = [
    '#type' => 'details',
    '#title' => t('CAPTCHA'),
    '#description' => t('Provides the <a href=":href">CAPTCHA</a> for adding challenges to forms.', [':href' => 'https://en.wikipedia.org/wiki/CAPTCHA']),
    '#open' => TRUE,
  ];
  $form['third_party_settings']['captcha']['replace_administration_mode'] = [
    '#type' => 'checkbox',
    '#title' => t('Replace <em>Add CAPTCHA administration links to forms</em> with CAPTCHA webform element'),
    '#description' => t('If checked and <a href=":href">Add CAPTCHA administration links to forms</a> is enabled, the CAPTCHA fieldset added to every form will create a new CAPTCHA webform element instead of tracking each webform\'s id.', $t_args) . '<br/><br/>' .
      t('It changes the "@from" link label and behavior to "@to"', $t_args)
    ,
    '#default_value' => $replace_administration_mode,
    '#return_value' => TRUE,
  ];
}

/**
 * Implements hook_webform_submission_form_alter().
 */
function captcha_webform_submission_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Make sure CAPTCHA admin mode is enabled and available to the current user.
  $config = \Drupal::config('captcha.settings');
  if (!$config->get('administration_mode')
    || !\Drupal::currentUser()->hasPermission('administer CAPTCHA settings')) {
    return;
  }

  // Make sure the CAPTCHA webform element is enabled.
  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  if ($element_manager->isExcluded('captcha')) {
    return;
  }

  // Make sure replace administrative mode is enabled.
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
  $replace_administration_mode = $third_party_settings_manager->getThirdPartySetting('captcha', 'replace_administration_mode');
  if (!$replace_administration_mode) {
    return;
  }

  // If the webform already has a CAPTCHA point is alread configured, do not
  // do anything.
  /* @var \Drupal\captcha\CaptchaPointInterface $captcha_point */
  $captcha_point = \Drupal::entityTypeManager()
    ->getStorage('captcha_point')
    ->load($form_id);
  if ($captcha_point) {
    return;
  }

  $form['#after_build'][] = '_captcha_webform_submission_form_after_build';
}

/**
 * After buld callback to add warning to CAPTCHA placement.
 */
function _captcha_webform_submission_form_after_build(array $form, FormStateInterface $form_state) {
  // Make sure 'Add CAPTCHA administration links to forms' is appended to the
  // webform.
  // @see /admin/config/people/captcha
  // @see captcha_form_alter()
  if (!isset($form['captcha']) || !isset($form['captcha']['add_captcha'])) {
    return $form;
  }

  /** @var \Drupal\webform\WebformSubmissionForm $form_object */
  $form_object = $form_state->getFormObject();
  $webform = $form_object->getWebform();

  // Determine if the current user can update this webform via the UI.
  $has_update_access = $webform->access('update')
    && \Drupal::moduleHandler()->moduleExists('webform_ui');

  // If the webform has a CAPTCHA element, display a link to edit the element.
  $elements = $webform->getElementsInitializedAndFlattened();
  foreach ($elements as $key => $element) {
    if (WebformElementHelper::isType($element, 'captcha')) {
      // Update the details element's title.
      $form['captcha']['#title'] = t('CAPTCHA: challenge enabled');

      // Replace 'Place a CAPTCHA here for untrusted users' link with link to
      // edit CAPTCHA element for this webform.
      if ($has_update_access) {
        $route_name = 'entity.webform_ui.element.edit_form';
        $route_parameters = ['webform' => $webform->id(), 'key' => $key];
        $route_options = ['query' => Drupal::destination()->getAsArray()];
        $form['captcha']['add_captcha'] = [
          '#type' => 'link',
          '#title' => t('Untrusted users will see a CAPTCHA element on this webform.'),
          '#url' => Url::fromRoute($route_name, $route_parameters, $route_options),
          '#prefix' => '<em>',
          '#suffix' => '</em>',
        ];
      }
      else {
        $form['captcha']['add_captcha'] = [
          '#markup' => t('Untrusted users will see a CAPTCHA element on this webform.'),
          '#prefix' => '<em>',
          '#suffix' => '</em>',
        ];
      }
      return $form;
    }
  }

  // Replace 'Place a CAPTCHA here for untrusted users' link with link to
  // add CAPTCHA element to this webform.
  if ($has_update_access) {
    $route_name = 'entity.webform_ui.element.add_form';
    $route_parameters = ['webform' => $webform->id(), 'type' => 'captcha'];
    $route_options = ['query' => Drupal::destination()->getAsArray()];
    $form['captcha']['add_captcha'] = [
      '#type' => 'link',
      '#title' => t('Add CAPTCHA element to this webform for untrusted users.'),
      '#url' => Url::fromRoute($route_name, $route_parameters, $route_options),
    ];
  }
  else {
    $form['captcha']['add_captcha'] = [
      '#type' => 'webform_message',
      '#message_message' => t('CAPTCHA should be added as an element to this webform.'),
      '#message_type' => 'warning',
    ];
  }
  return $form;
}
