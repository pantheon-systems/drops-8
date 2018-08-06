<?php

/**
 * @file
 * Theme hooks, preprocessor, and suggesttions.
 */

use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

/******************************************************************************/
// Theme hooks.
/******************************************************************************/

/**
 * Implements hook_theme().
 */
function webform_theme() {
  $info = [
    'webform_help' => [
      'variables' => ['info' => []],
    ],
    'webform_help_video_youtube' => [
      'variables' => ['youtube_id' => NULL, 'autoplay' => TRUE],
    ],

    'webform' => [
      'render element' => 'element',
    ],
    'webform_actions' => [
      'render element' => 'element',
    ],
    'webform_handler_debug_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],
    'webform_handler_email_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],
    'webform_handler_remote_post_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],

    'webform_confirmation' => [
      'variables' => ['webform' => NULL, 'source_entity' => NULL, 'webform_submission' => NULL],
    ],

    'webform_preview' => [
      'variables' => ['webform_submission' => NULL],
    ],

    'webform_submission' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL, 'type' => NULL],
    ],

    'webform_submission_navigation' => [
      'variables' => ['webform_submission' => NULL],
    ],
    'webform_submission_information' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL, 'open' => TRUE],
    ],

    'webform_submission_html' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL, 'excluded_elements' => [], 'exclude_empty' => TRUE],
    ],
    'webform_submission_table' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL, 'excluded_elements' => [], 'exclude_empty' => TRUE],
    ],
    'webform_submission_text' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL, 'excluded_elements' => [], 'exclude_empty' => TRUE],
    ],
    'webform_submission_yaml' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL],
    ],

    'webform_element_base_html' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],
    'webform_element_base_text' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],

    'webform_container_base_html' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],
    'webform_container_base_text' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],

    'webform_element_color_value_swatch' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],

    'webform_element_help' => [
      'variables' => ['help' => NULL],
    ],

    'webform_element_more' => [
      'variables' => ['more' => NULL, 'more_title' => '', 'attributes' => []],
    ],

    'webform_element_managed_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],
    'webform_element_audio_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],
    'webform_element_document_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],
    'webform_element_image_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL, 'style_name' => NULL, 'format' => NULL],
    ],
    'webform_element_video_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],

    'webform_email_message_html' => [
      'variables' => ['message' => '', 'webform_submission' => NULL, 'handler' => NULL],
    ],
    'webform_email_message_text' => [
      'variables' => ['message' => '', 'webform_submission' => NULL, 'handler' => NULL],
    ],

    'webform_horizontal_rule' => [
      'render element' => 'element',
    ],
    'webform_message' => [
      'render element' => 'element',
    ],
    'webform_section' => [
      'render element' => 'element',
    ],

    'webform_composite_address' => [
      'render element' => 'element',
    ],
    'webform_composite_contact' => [
      'render element' => 'element',
    ],
    'webform_composite_creditcard' => [
      'render element' => 'element',
    ],
    'webform_composite_location' => [
      'render element' => 'element',
    ],
    'webform_composite_link' => [
      'render element' => 'element',
    ],
    'webform_composite_name' => [
      'render element' => 'element',
    ],
    'webform_composite_telephone' => [
      'render element' => 'element',
    ],

    'webform_codemirror' => [
      'variables' => ['code' => NULL, 'type' => 'text'],
    ],

    'webform_progress' => [
      'variables' => [
        'webform' => NULL,
        'current_page' => NULL,
        'operation' => NULL,
      ],
    ],

    'webform_progress_bar' => [
      'variables' => [
        'webform' => NULL,
        'current_page' => NULL,
        'operation' => NULL,
        'max_pages' => 10,
      ],
    ],

    'webform_progress_tracker' => [
      'variables' => [
        'webform' => NULL,
        'current_page' => NULL,
        'operation' => NULL,
        'max_pages' => 10,
      ],
    ],
  ];

  // Since any rendering of a webform is going to require 'webform.theme.inc'
  // we are going to just add it to every template.
  foreach ($info as &$template) {
    $template['file'] = 'includes/webform.theme.template.inc';
  }

  return $info;
}

/**
 * Implements hook_theme_registry_alter().
 */
function webform_theme_registry_alter(&$theme_registry) {
  // Allow attributes to be defined for status messages so that #states
  // can be added to messages.
  // @see \Drupal\webform\Element\WebformMessage
  if (!isset($theme_registry['status_messages']['variables']['attributes'])) {
    $theme_registry['status_messages']['variables']['attributes'] = [];
  }
}

/******************************************************************************/
// Preprocessors.
/******************************************************************************/

/**
 * Prepares variables for single local action link templates.
 *
 * Applies custom link attributes to local actions.
 * Custom attributes are used to open Webform UI modals.
 *
 * Issue #2897396: Add a Reference Tab to track Webform nodes.
 * @see https://www.drupal.org/node/2897396
 *
 * @see template_preprocess_menu_local_action();
 * @see \Drupal\webform\WebformEntityHandlersForm
 * @see \Drupal\webform_ui\WebformUiEntityEditForm
 */
function webform_preprocess_menu_local_action(&$variables) {
  $link = $variables['element']['#link'];
  // Only need to update local actions with link attributes.
  if (!isset($link['attributes'])) {
    return;
  }

  $link += [
    'localized_options' => [],
  ];
  $link['localized_options']['attributes'] = $link['attributes'];
  $link['localized_options']['attributes']['class'][] = 'button';
  $link['localized_options']['attributes']['class'][] = 'button-action';
  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = [
    '#type' => 'link',
    '#title' => $link['title'],
    '#options' => $link['localized_options'],
    '#url' => $link['url'],
  ];
}

/**
 * Prepares variables for checkboxes templates.
 *
 * @see \Drupal\webform\Plugin\WebformElement\OptionsBase
 */
function webform_preprocess_checkboxes(&$variables) {
  _webform_preprocess_options($variables);
}

/**
 * Prepares variables for radios templates.
 *
 * @see \Drupal\webform\Plugin\WebformElement\OptionsBase
 */
function webform_preprocess_radios(&$variables) {
  _webform_preprocess_options($variables);
}

/**
 * Prepares variables for checkboxes and radios options templates.
 *
 * Below code must be trigger and template_preprocess_(radios|checkboxes) which
 * reset the element's 'attributes';
 */
function _webform_preprocess_options(&$variables) {
  $element = $variables['element'];

  if (!empty($element['#options_display'])) {
    $variables['attributes']['class'][] = 'js-webform-' . $element['#type'];
    $variables['attributes']['class'][] = 'webform-options-display-' . str_replace('_', '-', $element['#options_display']);
    $variables['#attached']['library'][] = 'webform/webform.element.options';
  }
}

/**
 * Prepares variables for details element templates.
 */
function webform_preprocess_details(&$variables) {
  // Move #description to #help for webform admin routes.
  _webform_preprocess_description_help($variables);

  // Add (read) more to #description.
  _webform_preprocess_details_description_more($variables);

  $element = &$variables['element'];

  // Restructure the details's title to include #help.
  if (isset($variables['title'])) {
    _webform_preprocess_help_title($variables['title'], $element);
  }
}

/**
 * Prepares variables for fieldset element templates.
 */
function webform_preprocess_fieldset(&$variables) {
  // Move #description to #help for webform admin routes.
  _webform_preprocess_description_help($variables);

  // Add (read) more to #description.
  _webform_preprocess_form_element_description_more($variables);

  $element = &$variables['element'];

  // Restructure the fieldset's legend title to include #help.
  if (isset($variables['legend']['title'])) {
    _webform_preprocess_help_title($variables['legend']['title'], $element);
  }
}

/**
 * Prepares variables for webform section element templates.
 */
function webform_preprocess_webform_section(&$variables) {
  // Move #description to #help for webform admin routes.
  _webform_preprocess_description_help($variables);

  $element = &$variables['element'];

  // Restructure the section's title to include #help.
  if (isset($variables['title'])) {
    _webform_preprocess_help_title($variables['title'], $element);
  }
}

/**
 * Prepares variables for datetime form wrapper templates.
 */
function webform_preprocess_datetime_wrapper(&$variables) {
  // Move #description to #help for webform admin routes.
  _webform_preprocess_description_help($variables);

  // Add (read) more to #description.
  _webform_preprocess_form_element_description_more($variables);

  $element = &$variables['element'];

  // Restructure the title to include #help.
  if (isset($variables['title'])) {
    _webform_preprocess_help_title($variables['title'], $element);
  }
}

/**
 * Prepares variables for form label templates.
 */
function webform_preprocess_form_element_label(&$variables) {
  $element = &$variables['element'];

  // Unset 'for' attribute.
  // @see \Drupal\webform\Element\WebformOtherBase::processWebformOther
  if (isset($element['#attributes']['for']) && $element['#attributes']['for'] === FALSE) {
    unset($variables['attributes']['for']);
  }

  // Restructure the label's title to include #help.
  if (!empty($element['#help'])) {
    _webform_preprocess_help_title($variables['title'], $element);
  }
}

/**
 * Prepares variables for form element templates.
 */
function webform_preprocess_form_element(&$variables) {
  $element = &$variables['element'];

  // Move #description to #help for webform admin routes.
  _webform_preprocess_description_help($variables);

  // Add (read) more to #description.
  _webform_preprocess_form_element_description_more($variables);

  // Add #help and #_title_display to label.
  // Note: #_title_display is used to track inline titles.
  // @see \Drupal\webform\Plugin\WebformElementBase::prepare
  $variables['label'] += array_intersect_key($element, array_flip(['#help', '#_title_display']));
}

/**
 * Prepares #description and #help properties for form element templates.
 */
function _webform_preprocess_description_help(&$variables) {
  $element = &$variables['element'];

  // Move #description to #help for webform admin routes.
  if (\Drupal::config('webform.settings')->get('ui.description_help')
    && \Drupal::service('webform.request')->isWebformAdminRoute()
    && isset($element['#description'])
    && !isset($element['#help'])
    && !empty($element['#title'])
    && !(isset($element['#title_display']) && in_array($element['#title_display'], ['attribute', 'invisible']))
  ) {
    $element['#help'] = $element['#description'];
    unset($variables['description']);
    if (is_array($element['#help'])) {
      $element['#help'] = \Drupal::service('renderer')->render($element['#help']);
    }
  }
}

/**
 * Append #help to title variable.
 */
function _webform_preprocess_help_title(&$variables, array &$element) {
  if (empty($element['#help'])) {
    return;
  }

  $variables = [
    'title' => (is_array($variables)) ? $variables : ['#markup' => $variables],
    'help' => [
      '#type' => 'webform_help',
    ] + array_intersect_key($element, array_flip(['#help'])),
  ];

  // Get #title_display and move help before title for 'inline' titles.
  if (isset($element['#_title_display'])) {
    // #_title_display is set
    // via \Drupal\webform\Plugin\WebformElementBase::prepare.
    $title_display = $element['#_title_display'];
  }
  elseif (isset($element['#title_display'])) {
    $title_display = $element['#title_display'];
  }
  else {
    $title_display = NULL;
  }

  if ($title_display == 'inline') {
    $variables['title']['#weight'] = 0;
    $variables['help']['#weight'] = -1;
  }
}

/**
 * Prepares #more property for form element template.
 *
 * @see template_preprocess_form_element()
 * @see form-element.html.twig
 * @see template_preprocess_datetime_wrapper()
 * @see datetime-wrapper.html.twig
 */
function _webform_preprocess_form_element_description_more(&$variables) {
  $element = &$variables['element'];
  if (empty($element['#more'])) {
    return;
  }

  // Make sure $variables['description']['content'] is a render array.
  if (!isset($variables['description'])) {
    $variables['description_display'] = $element['#description_display'];
    $description_attributes = [];
    if (!empty($element['#id'])) {
      $description_attributes['id'] = $element['#id'] . '--description';
    }
    $variables['description']['attributes'] = new Attribute($description_attributes);
    $variables['description']['content'] = [];
  }

  // Move content to content.description and convert description to
  // render array.
  $variables['description']['content'] = [
    'description' => is_array($variables['description']['content']) ? $variables['description']['content'] : ['#markup' => $variables['description']['content']],
  ];

  // If description display is invisible hie the description.
  if (isset($variables['description_display']) && $variables['description_display'] == 'invisible') {
    $variables['description']['content']['description']['#prefix'] = '<div class="visually-hidden">';
    $variables['description']['content']['description']['#suffix'] = '</div>';
    $variables['description_display'] = 'after';
  }

  // Add more element.
  $variables['description']['content']['more'] = [
    '#type' => 'webform_more',
    '#attributes' => (!empty($element['#id'])) ? ['id' => $element['#id'] . '--more'] : [],
  ] + array_intersect_key($element, array_flip(['#more', '#more_title']));
}

/**
 * Prepares #more property for details template.
 *
 * @see template_preprocess_details()
 * @see details.html.twig
 */
function _webform_preprocess_details_description_more(&$variables) {
  $element = &$variables['element'];
  if (empty($element['#more'])) {
    return;
  }

  // Convert description to render array.
  $variables['description'] = [
    'description' => is_array($variables['description']) ? $variables['description'] : ['#markup' => $variables['description']],
  ];

  // Add more element.
  $variables['description']['more'] = [
    '#type' => 'webform_more',
    '#attributes' => (!empty($element['#id'])) ? ['id' => $element['#id'] . '--more'] : [],
  ] + array_intersect_key($element, array_flip(['#more', '#more_title']));

}

/**
 * Prepares variables for file managed file templates.
 *
 * @see https://stackoverflow.com/questions/21842274/cross-browser-custom-styling-for-file-upload-button
 * @see template_preprocess_file_managed_file()
 */
function webform_preprocess_file_managed_file(&$variables) {
  $element = &$variables['element'];
  if (empty($element['#button'])) {
    return;
  }

  // Don't alter hidden file upload input.
  if (isset($element['upload']['#access']) && $element['upload']['#access'] === FALSE) {
    return;
  }

  // Create an unique id for the file upload input and label.
  $button_id = Html::getUniqueId($variables['element']['upload']['#id'] . '-button');

  // Create a label that is styled like an action button.
  $label = [
    '#type' => 'html_tag',
    '#tag' => 'label',
    '#value' => (isset($element['#button__title'])) ? $element['#button__title'] : (empty($element['#multiple']) ? t('Choose file') : t('Choose files')),
    '#attributes' => (isset($element['#button__attributes'])) ? $element['#button__attributes'] : [],
  ];

  // Add 'for' attribute.
  $label['#attributes']['for'] = $button_id;

  // Add default button classes.
  if (empty($label['#attributes']['class'])) {
    $label['#attributes']['class'][] = 'button';
    $label['#attributes']['class'][] = 'button-action';
  }

  // Add .webform-file-button.
  $label['#attributes']['class'][] = 'webform-file-button';

  // Make sure the label is first.
  $element = ['label' => $label] + $element;

  // Set the custom button ID for file upload input.
  $element['upload']['#attributes']['id'] = $button_id;

  // Hide the file upload.
  $element['upload']['#attributes']['class'][] = 'webform-file-button-input';

  // Attach library.
  $element['#attached']['library'][] = 'webform/webform.element.file.button';
}

/**
 * Prepares variables for file link templates.
 *
 * @see webform_file_access
 */
function webform_preprocess_file_link(&$variables) {
  /** @var \Drupal\file\FileInterface $file */
  $file = $variables['file'];
  $file = ($file instanceof File) ? $file : File::load($file->fid);

  // Remove link to temporary anonymous private file uploads.
  if ($file->isTemporary() && $file->getOwner()->isAnonymous() && strpos($file->getFileUri(), 'private://webform/') === 0) {
    $variables['link'] = $file->getFilename();
  }
}

/**
 * Prepares variables for image.
 *
 * Make sure the image src for the 'webform_image_file' src is an absolute URL.
 */
function webform_preprocess_image(&$variables) {
  global $base_url;
  if (isset($variables['attributes']['class']) && in_array('webform-image-file', (array) $variables['attributes']['class'])) {
    $variables['attributes']['src'] = $base_url . preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $variables['attributes']['src']);
  }
}

/******************************************************************************/
// Theme suggestions.
/******************************************************************************/

/**
 * Provides alternate named suggestions for a specific theme hook.
 *
 * @param array $variables
 *   An array of variables passed to the theme hook.
 * @param string $hook
 *   The base hook name.
 *
 * @return array
 *   An array of theme suggestions.
 */
function _webform_theme_suggestions(array $variables, $hook) {
  $suggestions = [];

  if ($hook == 'webform' && isset($variables['element']) && isset($variables['element']['#webform_id'])) {
    $suggestions[] = $hook . '__' . $variables['element']['#webform_id'];
  }
  elseif (strpos($hook, 'webform_element_base_') === 0 || strpos($hook, 'webform_container_base_') === 0) {
    $element = $variables['element'];

    if (!empty($element['#type'])) {
      $type = $element['#type'];
      $name = $element['#webform_key'];

      $suggestions[] = $hook . '__' . $type;
      $suggestions[] = $hook . '__' . $type . '__' . $name;
    }
  }
  elseif (isset($variables['webform_submission'])) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $variables['webform_submission'];
    $webform = $webform_submission->getWebform();
    $suggestions[] = $hook . '__' . $webform->id();
  }
  elseif (isset($variables['webform'])) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $variables['webform'];
    $suggestions[] = $hook . '__' . $webform->id();
  }

  return $suggestions;
}

/**
 * Helper function used to generate hook_theme_suggestions_HOOK().
 */
function _webform_devel_hook_theme_suggestions_generate() {
  $theme = webform_theme();
  print '<pre>';
  foreach ($theme as $hook => $info) {
    $suggestion = FALSE;
    if ($hook == 'webform') {
      $suggestion = TRUE;
    }
    elseif (strpos($hook, 'webform_element_base_') === 0 || strpos($hook, 'webform_container_base_') === 0) {
      $suggestion = TRUE;
    }
    elseif (isset($info['variables'])
      && !array_key_exists('element', $info['variables'])
      && (array_key_exists('webform_submission', $info['variables']) || array_key_exists('webform', $info['variables']))) {
      $suggestion = TRUE;
    }

    if ($suggestion) {
      print "/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_$hook(array \$variables) {
  return _webform_theme_suggestions(\$variables, '$hook');
}

";
    }
  }
  print '</pre>';
  exit;
}

/******************************************************************************/
// Theme suggestions.
// Generate using _webform_devel_hook_theme_suggestions_generate();
/******************************************************************************/

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform(array $variables) {
  return _webform_theme_suggestions($variables, 'webform');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_confirmation(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_confirmation');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_preview(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_preview');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_navigation(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_navigation');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_information(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_information');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_table(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_table');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_yaml(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_yaml');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_element_base_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_element_base_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_element_base_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_element_base_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_container_base_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_container_base_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_container_base_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_container_base_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_email_message_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_email_message_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_email_message_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_email_message_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_progress(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_progress');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_progress_bar(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_progress_bar');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_progress_tracker(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_progress_tracker');
}
