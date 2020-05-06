<?php

/**
 * @file
 * Webform module translation hooks.
 *
 * @see webform_preprocess_table()
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformYaml;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\field\Entity\FieldConfig;

/**
 * Implements hook_form_FORM_ID_alter() for language_content_settings_form().
 */
function webform_form_language_content_settings_form_alter(array &$form, FormStateInterface $form_state) {
  // Completely remove webform_submission from Content language admin
  // settings form, only when there are no previously saved
  // 'language.content_settings.webform_submission.*' config files.
  $has_saved_webform_submissions = count(\Drupal::configFactory()->listAll('language.content_settings.webform_submission.')) ? TRUE : FALSE;
  if (!$has_saved_webform_submissions) {
    unset($form['#label']['webform_submission']);
    unset($form['entity_types']['#options']['webform_submission']);
    unset($form['settings']['webform_submission']);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function webform_form_locale_translate_edit_form_alter(&$form, FormStateInterface $form_state) {
  // Don't allow YAML to be validated using locale string translation.
  foreach (Element::children($form['strings']) as $key) {
    $element =& $form['strings'][$key];
    if ($element['original']
      && !empty($element['original']['#plain_text'])
      && preg_match("/'#[^']+':/", $element['original']['#plain_text'])
      && WebformYaml::isValid($element['original']['#plain_text'])) {
      $element['original'] = [
        '#theme' => 'webform_codemirror',
        '#code' => $element['original']['#plain_text'],
        '#type' => 'yaml',
      ];
      $element['translations'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => t("Webforms can only be translated via the Webform's (Configuration) Translate tab."),
      ];
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function webform_form_config_translation_add_form_alter(&$form, FormStateInterface $form_state, $is_new = TRUE) {
  // Manually apply YAML editor to text field that store YAML data.
  foreach ($form['config_names'] as $config_name => &$config_element) {
    if ($config_name == 'webform.settings') {
      _webform_form_config_translate_add_form_alter_yaml_element($config_element['test']['types']);
      _webform_form_config_translate_add_form_alter_yaml_element($config_element['test']['names']);
    }
    elseif ($config_name == 'block.block.webform') {
      _webform_form_config_translate_add_form_alter_yaml_element($config_element['settings']['default_data']);;
    }
    elseif (strpos($config_name, 'field.field.') === 0
      && preg_match('/^field\.field\.([_a-z0-9]+)\.([_a-z0-9]+)\.([_a-z0-9]+)$/', $config_name, $match)
      && isset($config_element['default_value'])) {
      $field = FieldConfig::loadByName($match[1], $match[2], $match[3]);
      if ($field->getType() === 'webform') {
        foreach (Element::children($config_element['default_value']) as $key) {
          if (isset($config_element['default_value'][$key]['default_data'])) {
            _webform_form_config_translate_add_form_alter_yaml_element($config_element['default_value'][$key]['default_data']);
          }
        }
      }
    }
    elseif (strpos($config_name, 'webform.webform_options.') === 0
      || strpos($config_name, 'webform_options_custom.webform_options_custom.') === 0) {
      _webform_form_config_translate_add_form_alter_yaml_element($config_element['options']);
    }
    elseif (strpos($config_name, 'webform.webform.') === 0) {
      $webform_id = str_replace('webform.webform.', '', $config_name);
      $webform = Webform::load($webform_id);

      /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
      $translation_manager = \Drupal::service('webform.translation_manager');
      $translation_langcode = $form_state->get('config_translation_language')->getId();
      $source_elements = $translation_manager->getSourceElements($webform);
      $translation_elements = $translation_manager->getTranslationElements($webform, $translation_langcode);
      $source_value = WebformYaml::encode($source_elements);
      $translation_value = WebformYaml::encode($translation_elements);

      _webform_form_config_translate_add_form_alter_yaml_element($config_element['elements'], $source_value, $translation_value);

      $config_element['elements']['translation']['#description'] = t('Please note: Custom properties will be automatically removed.');
      $form_state->set('webform_config_name', $config_name);
      $form_state->set('webform_source_elements', $source_elements);

      // Tweak default 128 max length for certain settings.
      $config_element['title']['translation']['#maxlength'] = 255;
      $config_element['settings']['submission_label']['translation']['#maxlength'] = NULL;

      $form['#validate'][] = '_webform_form_config_translate_add_form_validate';
    }
  }
}

/**
 * Validate callback; Validates and cleanups webform elements.
 */
function _webform_form_config_translate_add_form_validate(&$form, FormStateInterface $form_state) {
  if ($form_state::hasAnyErrors()) {
    return;
  }

  $values = $form_state->getValues();

  $config_name = $form_state->get('webform_config_name');
  $source_elements = $form_state->get('webform_source_elements');
  $submitted_translation_elements = Yaml::decode($values['translation']['config_names'][$config_name]['elements']);
  $translation_elements = $source_elements;

  // Remove all custom translation properties.
  WebformElementHelper::merge($translation_elements, $submitted_translation_elements);

  // Remove any translation property that has not been translated.
  _webform_form_config_translate_add_form_filter_elements($translation_elements, $source_elements);

  // Update webform value.
  $values['translation']['config_names'][$config_name]['elements'] = ($translation_elements) ? Yaml::encode($translation_elements) : '';
  $form_state->setValues($values);
}

/**
 * Merge element properties.
 *
 * @param array $translation_elements
 *   An array of elements.
 * @param array $source_elements
 *   An array of elements to be merged.
 */
function _webform_form_config_translate_add_form_filter_elements(array &$translation_elements, array $source_elements) {
  foreach ($translation_elements as $key => &$translation_element) {
    if (!isset($source_elements[$key])) {
      continue;
    }
    $source_element = $source_elements[$key];
    if ($translation_element == $source_element) {
      unset($translation_elements[$key]);
    }
    elseif (is_array($translation_element)) {
      _webform_form_config_translate_add_form_filter_elements($translation_element, $source_element);
      if (empty($translation_element)) {
        unset($translation_elements[$key]);
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function webform_form_config_translation_edit_form_alter(&$form, FormStateInterface $form_state) {
  webform_form_config_translation_add_form_alter($form, $form_state, FALSE);
}

/**
 * Alter translated config entity property.
 *
 * @param array $element
 *   A webform element containing 'source' and 'translation'.
 * @param string $source_value
 *   (optional) The custom config source value.
 * @param string $translation_value
 *   (optional) The custom config translation value.
 */
function _webform_form_config_translate_add_form_alter_yaml_element(array &$element, $source_value = NULL, $translation_value = NULL) {
  // Source.
  $source_value = $source_value ?: trim(strip_tags($element['source']['#markup']));
  if ($source_value === ((string) t('(Empty)'))) {
    $source_value = '';
  }
  $element['source']['#wrapper_attributes']['class'][] = 'webform-translation-source';
  $element['source']['value'] = [
    '#type' => 'webform_codemirror',
    '#mode' => 'yaml',
    '#value' => ($source_value) ? WebformYaml::tidy($source_value) : '',
    '#disabled' => TRUE,
    '#attributes' => ['readonly' => TRUE],
  ];
  unset($element['source']['#markup']);

  // Translation.
  $element['translation']['#type'] = 'webform_codemirror';
  $element['translation']['#mode'] = 'yaml';
  if ($translation_value) {
    $element['translation']['#default_value'] = WebformYaml::tidy($translation_value);
  }
  $element['translation']['#default_value'] = trim($element['translation']['#default_value']);
  $element['#attached']['library'][] = 'webform/webform.admin.translation';
}

/******************************************************************************/
// Lingotek integration.
/******************************************************************************/

/**
 * Implements hook_lingotek_config_entity_document_upload().
 */
function webform_lingotek_config_entity_document_upload(array &$source_data, ConfigEntityInterface &$entity, &$url) {
  switch ($entity->getEntityTypeId()) {
    case 'field_config':
      // Convert webform default data YAML string to an associative array.
      /** @var \Drupal\field\Entity\FieldConfig $entity */
      if ($entity->getFieldStorageDefinition()->getType() === 'webform') {
        foreach ($source_data as &$field_settings) {
          foreach ($field_settings as $setting_name => $setting_value) {
            if (preg_match('/\.default_data$/', $setting_name)) {
              $field_settings[$setting_name] = Yaml::decode($field_settings[$setting_name]);
            }
          }
        }
        _webform_lingotek_encode_tokens($field_settings);
      }
      break;

    case 'webform';
      /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
      $translation_manager = \Drupal::service('webform.translation_manager');

      // Replace elements with just the translatable properties
      // (i.e. #title, #description, #options, etc…) so that Lingotek's
      // translation services can correctly translate each element.
      $translation_elements = $translation_manager->getTranslationElements($entity, $entity->language()->getId());
      $source_data['elements'] = $translation_elements;

      _webform_lingotek_encode_tokens($source_data);
      break;

    case 'webform_options';
    case 'webform_options_custom';
      // Convert options YAML string to an associative array.
      $options = Yaml::decode($source_data['options']);;

      // Extract optgroups from the options and append them as '_optgroups_'
      // to the options so that the optgroups can be translated.
      $optgroups = [];
      foreach ($options as $option_value => $option_text) {
        if (is_array($option_text)) {
          $optgroups[$option_value] = $option_value;
        }
      }
      if ($optgroups) {
        $options['_optgroups_'] = $optgroups;
      }

      // Update source data's options.
      $source_data['options'] = $options;
      break;
  }
}

/**
 * Implements hook_lingotek_config_entity_translation_presave().
 */
function webform_lingotek_config_entity_translation_presave(ConfigEntityInterface &$translation, $langcode, &$data) {
  switch ($translation->getEntityTypeId()) {
    case 'field_config':
      // Convert webform default data associative array back to YAML string.
      /** @var \Drupal\field\Entity\FieldConfig $translation */
      if ($translation->getFieldStorageDefinition()->getType() === 'webform') {
        foreach ($data as &$field_settings) {
          _webform_lingotek_encode_tokens($field_settings);
          foreach ($field_settings as $setting_name => $setting_value) {
            if (preg_match('/\.default_data$/', $setting_name)) {
              $field_settings[$setting_name] = $field_settings[$setting_name] ? Yaml::encode($field_settings[$setting_name]) : '';
            }
          }
        }
      }
      break;

    case 'webform';
      _webform_lingotek_decode_tokens($data);

      /** @var \Drupal\webform\WebformInterface $translation */
      $translation->setElements($data['elements']);
      $data['elements'] = Yaml::encode($data['elements']);
      break;

    case 'webform_options';
    case 'webform_options_custom';
      $options = $data['options'];
      // If '_optgroups_' are defined we need to translate the optgroups.
      if (isset($options['_optgroups_'])) {
        // Get optgroup from options.
        $optgroups = $options['_optgroups_'];
        unset($options['_optgroups_']);

        // Build translated optgroup options.
        $optgroup_options = [];
        foreach ($options as $option_value => $option_text) {
          if (is_array($option_text)) {
            $optgroups_options[$optgroups[$option_value]] = $option_text;
          }
          else {
            $optgroup_options[$option_value] = $option_text;
          }
        }
        // Replace options with optgroup options.
        $options = $optgroups_options;
      }

      /** @var \Drupal\webform\WebformOptionsInterface $translation */
      // Convert options associative array back to YAML string.
      $translation->setOptions($options);
      $data['options'] = Yaml::encode($options);
      break;
  }
}

/**
 * Implements hook_lingotek_config_object_document_upload().
 */
function webform_lingotek_config_object_document_upload(array &$data, $config_name) {
  if ($config_name !== 'webform.settings') {
    return;
  }

  $data['webform.settings']['test.types'] = Yaml::decode($data['webform.settings']['test.types']);
  $data['webform.settings']['test.names'] = Yaml::decode($data['webform.settings']['test.names']);

  _webform_lingotek_encode_tokens($data);
}

/**
 * Implements hook_lingotek_config_object_translation_presave().
 */
function webform_lingotek_config_object_translation_presave(array &$data, $config_name) {
  if ($config_name !== 'webform.settings') {
    return;
  }

  _webform_lingotek_decode_tokens($data);

  $data['webform.settings']['test.types'] = Yaml::encode($data['webform.settings']['test.types']);
  $data['webform.settings']['test.names'] = Yaml::encode($data['webform.settings']['test.names']);
}

/******************************************************************************/
// Lingotek decode/encode token functions.
/******************************************************************************/

/**
 * Encode all tokens so that they won't be translated.
 *
 * @param array $data
 *   An array of data.
 */
function _webform_lingotek_encode_tokens(array &$data) {
  $yaml = Yaml::encode($data);
  $yaml = preg_replace_callback(
    '/\[([a-z][^]]+)\]/',
    function ($matches) {
      // Encode all token characters to HTML entities.
      // @see https://stackoverflow.com/questions/6720826/php-convert-all-characters-to-html-entities.
      $replacement = mb_encode_numericentity($matches[1], [0x000000, 0x10ffff, 0, 0xffffff], 'UTF-8');
      return "[$replacement]";
    },
    $yaml
  );
  $data = Yaml::decode($yaml);
}

/**
 * Decode all tokens after string have been translated.
 *
 * @param array $data
 *   An array of data.
 */
function _webform_lingotek_decode_tokens(array &$data) {
  $yaml = Yaml::encode($data);
  $yaml = preg_replace_callback(
    '/\[([^]]+?)\]/',
    function ($matches) {
      // Decode token HTML entities to characters.
      // @see https://stackoverflow.com/questions/6720826/php-convert-all-characters-to-html-entities.
      $token = mb_decode_numericentity($matches[1], [0x000000, 0x10ffff, 0, 0xffffff], 'UTF-8');
      return "[$token]";
    },
    $yaml
  );
  $data = Yaml::decode($yaml);
}
