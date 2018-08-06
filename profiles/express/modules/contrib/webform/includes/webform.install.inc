<?php

/**
 * @file
 * Webform install helper functions.
 */

use Drupal\Core\Serialization\Yaml;
use Drupal\system\Entity\Action;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Update admin settings to reflect changes in the default settings.
 *
 * If you are moving or updating any admin settings this must be explicitly
 * done via an update hook.
 *
 * @param bool $reset
 *   If set TRUE old admin settings will be completly deleted.
 *
 * @see drush_webform_repair()
 */
function _webform_update_admin_settings($reset = FALSE) {
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');

  $current_settings = $admin_config->getRawData();
  $admin_settings = Yaml::decode(file_get_contents(drupal_get_path('module', 'webform') . '/config/install/webform.settings.yml'));
  // Note, admin settings are always grouped into associative array,
  // except for the langcode.
  foreach ($admin_settings as $group => $settings) {
    // Handle the rare case the we are adding a new group the admin settings.
    if (!isset($current_settings[$group])) {
      continue;
    }

    // Completely copy the format, langcode, and third_party_settings.
    if (in_array($group, ['format', 'langcode', 'third_party_settings'])) {
      if (isset($current_settings[$group])) {
        $admin_settings[$group] = $current_settings[$group];
      }
    }
    elseif ($reset) {
      // Copy only group's settings that are defined in admin settings.
      // This will cause old settings to be completely deleted.
      foreach ($settings as $name => $value) {
        if (isset($current_settings[$group][$name])) {
          $admin_settings[$group][$name] = $current_settings[$group][$name];
        }
      }
    }
    else {
      // Loop through the group's settings and apply all existing settings to
      // the default admin settings.
      foreach ($current_settings[$group] as $name => $value) {
        $admin_settings[$group][$name] = $value;
      }
    }
  }

  // If not reset, make sure all the current settings are preserved.
  if (!$reset) {
    $admin_settings += $current_settings;
  }

  $admin_config->setData($admin_settings)->save();
}

/**
 * Update webform setting to reflect changes in the default settings.
 *
 * This function can be used to apply new webform settings to all existing
 * webforms.
 *
 * @see \Drupal\webform\Entity\Webform::setSettings
 */
function _webform_update_webform_settings() {
  $default_properties = [
    'langcode' => 'en',
    'status' => WebformInterface::STATUS_OPEN,
    'dependencies' => [],
    'open' => NULL,
    'close' => NULL,
    'uid' => '',
    'template' => FALSE,
    'id' => '',
    'title' => '',
    'description' => '',
    'category' => '',
    'elements' => '',
    'css' => '',
    'javascript' => '',
    'settings' => [],
    'access' => [],
    'handlers' => [],
  ];

  $default_settings = Webform::getDefaultSettings();
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    // Get data.
    $data = $webform_config->getRawData();

    // Always apply the default properties.
    $properties = $default_properties;
    // Now apply defined properties.
    foreach ($data as $name => $value) {
      $properties[$name] = $value;
    }
    // Set properties.
    $data = $properties;

    // Always apply the default settings.
    $settings = $default_settings;
    // Now apply custom settings.
    foreach ($data['settings'] as $name => $value) {
      $settings[$name] = $value;
    }
    // Set settings.
    $data['settings'] = $settings;

    // Save data.
    $webform_config->setData($data)->save();
  }
}

/**
 * Update webform handler setting to reflect changes in a handler's default configuration.
 *
 * Must be called by passing in the handler's class using ::class.
 *
 * Example usage:
 * @code
 * _webform_update_webform_handler_class_settings(EmailWebformHandler::class);
 * @endcode
 *
 * @param string $handler_class
 *   A handler class instance.
 *
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 */
function _webform_update_webform_handler_class_settings($handler_class) {
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $has_handler = FALSE;
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof $handler_class) {
        $has_handler = TRUE;
        $configuration = $handler->getConfiguration();

        $settings = $handler->defaultConfiguration();
        foreach ($configuration['settings'] as $settings_key => $setting_value) {
          $settings[$settings_key] = $setting_value;
        }
        $configuration['settings'] = $settings;
        $handler->setConfiguration($configuration);
      }
    }
    if ($has_handler) {
      $webform->save();
    }
  }
}

/**
 * Update webform options setting to reflect changes in the default settings.
 *
 * This function can be used to apply new webform options configuration to
 * all existing webforms options.
 *
 * @see \Drupal\webform\Entity\WebformOptions
 */
function _webform_update_options_settings() {
  $default_properties = [
    'langcode' => 'en',
    'status' => TRUE,
    'dependencies' => [],
    'id' => '',
    'label' => '',
    'category' => '',
    'options' => '',
  ];

  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform_options.*' configuration.
  foreach ($config_factory->listAll('webform.webform_options.') as $webform_config_name) {
    $webform_options_config = $config_factory->getEditable($webform_config_name);

    // Get data.
    $data = $webform_options_config->getRawData();

    // Always apply the default properties.
    $properties = $default_properties;
    // Now apply defined properties.
    foreach ($data as $name => $value) {
      $properties[$name] = $value;
    }

    // Set properties.
    $data = $properties;

    // Save data.
    $webform_options_config->setData($data)->save();
  }
}

/**
 * Update or install any new system.actions.* config entities.
 */
function _webform_update_actions() {
  $files = file_scan_directory(drupal_get_path('module', 'webform') . '/config', '/^system.action..*\.yml$/');
  foreach ($files as $path => $file) {
    $action_id = str_replace('system.action.', '', $file->name);
    $action = Action::load($action_id);
    if (!$action) {
      // Install new action.
      $data = Yaml::decode(file_get_contents($path));
      $action = Action::create($data);
      $action->trustData()->save();
    }
  }
}

/**
 * Update webform field storage definitions.
 *
 * @see \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem::schema
 */
function _webform_update_field_storage_definitions() {
  $manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\field\FieldStorageConfigInterface[] $fields */
  $fields = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['type' => 'webform']);
  foreach ($fields as $field) {
    $field_name = $field->getName();
    $entity_type = $field->getTargetEntityTypeId();
    $manager->updateFieldStorageDefinition($manager->getFieldStorageDefinition($field_name, $entity_type));
  }
}

/**
 * Update webform submission storage schema.
 *
 * @see \Drupal\webform\WebformSubmissionStorageSchema
 */
function _webform_update_webform_submission_storage_schema() {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $manager->updateEntityType($manager->getEntityType('webform_submission'));
}
