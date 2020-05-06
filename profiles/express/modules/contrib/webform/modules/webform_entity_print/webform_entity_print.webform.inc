<?php

/**
 * @file
 * Integrates third party settings for the Webform Entity Print module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Build webform entity print form.
 *
 * @param array $element
 *   The entity print element.
 * @param array $template_settings
 *   Template settings.
 * @param array $export_type_settings
 *   Export types settings.
 * @param array $default_template_settings
 *   Default template settings.
 * @param array $default_export_type_settings
 *   Default export types settings.
 */
function _webform_entity_print_form(array &$element, array $template_settings, array $export_type_settings, array $default_template_settings = [], array $default_export_type_settings = []) {
  $element['webform_entity_print'] = [
    '#type' => 'details',
    '#title' => t('Entity print'),
    '#description' => t('Allows webform submissions to be converted to a PDF'),
  ];

  // Video.
  /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('webform.help_manager');
  $element['webform_entity_print']['help'] = [
    '#theme' => 'webform_help',
    '#info' => $help_manager->getHelp('webform_entity_print'),
  ];

  // Template.
  $template_settings += [
    'header' => '',
    'footer' => '',
    'css' => '',
  ];
  $element['webform_entity_print']['template'] = [
    '#type' => 'details',
    '#title' => t('Template'),
  ];
  $element['webform_entity_print']['template']['header'] = [
    '#type' => 'webform_html_editor',
    '#title' => t('Header'),
    '#description' => ($default_template_settings)
      ? t('Enter custom header to be displayed above printed webform submissions.')
      : t('Enter custom header to be displayed above all printed webform submissions.'),
    '#default_value' => $template_settings['header'],
  ];
  $element['webform_entity_print']['template']['footer'] = [
    '#type' => 'webform_html_editor',
    '#title' => t('Footer'),
    '#default_value' => $template_settings['footer'],
    '#description' => ($default_template_settings)
      ? t('Enter custom footer to be displayed below printed webform submissions.')
      : t('Enter custom footer to be displayed below all printed webform submissions.'),
  ];
  $element['webform_entity_print']['template']['css'] = [
    '#type' => 'webform_codemirror',
    '#mode' => 'css',
    '#title' => t('CSS'),
    '#default_value' => $template_settings['css'],
    '#description' => ($default_template_settings)
      ? t('Enter custom print CSS to be attached to printed webform submissions.')
      : t('Enter custom print CSS to be attached to all printed webform submissions.'),
  ];
  /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
  $token_manager = \Drupal::service('webform.token_manager');
  $element['webform_entity_print']['template']['token_tree_link'] = $token_manager->buildTreeElement();

  // Export types.
  $export_types = _webform_entity_print_get_export_types();
  foreach ($export_types as $export_type => $definition) {
    $t_args = ['@label' => $definition['label']];
    $defaults = ['enabled' => FALSE, 'link_text' => '', 'link_attributes' => []];
    $default_settings = (isset($default_export_type_settings[$export_type])) ? $default_export_type_settings[$export_type] : $defaults;
    $settings = (isset($export_type_settings[$export_type])) ? $export_type_settings[$export_type] : [];
    $settings += $defaults;
    $states_trigger = '.js-webform_entity_print-' . $export_type;
    $element['webform_entity_print']['export_types'][$export_type] = [
      '#type' => 'details',
      '#title' => t('@label link', $t_args),
    ];
    $element['webform_entity_print']['export_types'][$export_type]['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable @label link', $t_args),
      '#return_value' => TRUE,
      '#default_value' => ($default_settings['enabled']) ? TRUE : $settings['enabled'],
      '#disabled' => $default_settings['enabled'],
      '#attributes' => ['class' => ['js-webform_entity_print-' . $export_type]],
    ];
    $element['webform_entity_print']['export_types'][$export_type]['link_text'] = [
      '#type' => 'textfield',
      '#title' => t('@label link text', $t_args),
      '#description' => ($default_settings['link_text']) ? t('Defaults to: %value', ['%value' => $default_settings['link_text']]) : '',
      '#default_value' => $settings['link_text'],
      '#states' => [
        'visible' => [
          $states_trigger => ['checked' => TRUE],
        ],
      ],
    ];
    if (empty($default_settings['link_text'])) {
      $element['webform_entity_print']['export_types'][$export_type]['link_text']['#states']['required'] = [
        $states_trigger => ['checked' => TRUE],
      ];
    }
    $element['webform_entity_print']['export_types'][$export_type]['link_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => t('@label link attributes', $t_args),
      '#default_value' => $settings['link_attributes'],
      '#states' => [
        'visible' => [
          $states_trigger => ['checked' => TRUE],
        ],
      ],
    ];
  }
}

/**
 * Implements hook_webform_admin_third_party_settings_form_alter().
 */
function webform_entity_print_webform_admin_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
  $template_settings = $third_party_settings_manager->getThirdPartySetting('webform_entity_print', 'template') ?: [];
  $export_type_settings = $third_party_settings_manager->getThirdPartySetting('webform_entity_print', 'export_types') ?: [];

  // Set export type default values.
  $export_types = _webform_entity_print_get_export_types();
  foreach ($export_types as $export_type => $definition) {
    $t_args = ['@label' => $definition['label']];
    $export_type_settings += [$export_type => []];
    $export_type_settings[$export_type] += [
      'enabled' => FALSE,
      'link_text' => t('Download @label', $t_args),
      'link_attributes' => ['class' => ['button']],
    ];
  }

  _webform_entity_print_form(
    $form['third_party_settings'],
    $template_settings,
    $export_type_settings
  );

  // Add debug settings.
  $form['third_party_settings']['webform_entity_print']['debug'] = [
    '#type' => 'checkbox',
    '#title' => t('Debug generated documents'),
    '#description' => t('If checked, administrators will see debug links below each export type.'),
    '#return_value' => TRUE,
    '#default_value' => $third_party_settings_manager->getThirdPartySetting('webform_entity_print', 'debug') ?: FALSE,
  ];

  $form['#validate'][] = '_webform_entity_print_form_submit';
}

/**
 * Implements hook_webform_third_party_settings_form_alter().
 */
function webform_entity_print_webform_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
  $default_template_settings = $third_party_settings_manager->getThirdPartySetting('webform_entity_print', 'template') ?: [];
  $default_export_type_settings = $third_party_settings_manager->getThirdPartySetting('webform_entity_print', 'export_types') ?: [];

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $form_state->getFormObject()->getEntity();
  $template_settings = $webform->getThirdPartySetting('webform_entity_print', 'template') ?: [];
  $export_type_settings = $webform->getThirdPartySetting('webform_entity_print', 'export_types') ?: [];

  _webform_entity_print_form(
    $form['third_party_settings'],
    $template_settings,
    $export_type_settings,
    $default_template_settings,
    $default_export_type_settings
  );
  $form['#validate'][] = '_webform_entity_print_form_submit';
}

/**
 * Submit callback for clearing library_info cache.
 *
 * @param array $form
 *   An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\webform_entity_print\Plugin\Derivative\WebformEntityPrintWebformExporterDeriver
 */
function _webform_entity_print_form_submit(array &$form, FormStateInterface $form_state) {
  if (!$form_state->hasAnyErrors()) {
    // Invalidate library_info cache tag.
    // @see webform_entity_print_library_info_build()
    Cache::invalidateTags(['library_info']);
  }
}

/**
 * Get export types.
 *
 * @return array
 *   Associative array of export types.
 */
function _webform_entity_print_get_export_types() {
  $export_types = \Drupal::service('plugin.manager.entity_print.export_type')->getDefinitions();

  // Remove unsupported export types.
  // Issue #2733781: Add Export to Word Support.
  // @see https://www.drupal.org/project/entity_print/issues/2733781
  unset($export_types['word_docx']);
  // Issue #2735559: Add Export to ePub.
  // @see https://www.drupal.org/project/entity_print/issues/2735559
  unset($export_types['epub']);

  return $export_types;
}
