<?php

/**
 * @file
 * Archived Webform update hooks.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformInterface;

/******************************************************************************/
// Webform-8.x-5.0-beta1 - December 7, 2016 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta2 - December 8, 2016 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta3 - December, 21 2016.
/******************************************************************************/

/**
 * Issue #2834203: Convert webform field target_id to 32 characters.
 */
function webform_update_8001() {
  $database_schema = \Drupal::database()->schema();
  $schema = \Drupal::keyValue('entity.storage_schema.sql')->getAll();
  foreach ($schema as $item_name => $item) {
    foreach ($item as $table_name => $table_schema) {
      foreach ($table_schema as $schema_key => $schema_data) {
        if ($schema_key == 'fields') {
          foreach ($schema_data as $field_name => $field_data) {
            if (preg_match('/_target_id$/', $field_name) && $field_data['description'] == 'The ID of the webform entity.' && $schema[$item_name][$table_name]['fields'][$field_name]['length'] === 255) {
              $schema[$item_name][$table_name]['fields'][$field_name]['length'] = 32;
              if ($database_schema->tableExists($table_name)) {
                $database_schema->changeField($table_name, $field_name, $field_name, $schema[$item_name][$table_name]['fields'][$field_name]);
              }
            }
          }
        }
      }
    }
  }
  \Drupal::keyValue('entity.storage_schema.sql')->setMultiple($schema);
}

/**
 * Issue #2834572: Refactor and improve token management.
 */
function webform_update_8002() {
  $config_factory = \Drupal::configFactory();

  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $yaml = Yaml::encode($settings_config->getRawData());
  $yaml = str_replace('[webform_submission:', '[webform_submission:', $yaml);
  $settings_config->setData(Yaml::decode($yaml));
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = str_replace('[webform_submission:', '[webform_submission:', $yaml);
    $webform_config->setData(Yaml::decode($yaml));
    $webform_config->save();
  }
}

/**
 * Issue #2834654: Add close button to messages.
 */
function webform_update_8003() {
  // Change webform.* to webform.* state.
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $state = \Drupal::state()->get('webform.' . $webform->id(), NULL);
    if ($state !== NULL) {
      \Drupal::state()->set('webform.webform.' . $webform->id(), $state);
      \Drupal::state()->delete('webform.' . $webform->id());
    }
  }
}

/**
 * Issue #2836948: Problem with autocomplete field. Change '#autocomplete_options' to '#autocomplete_items'.
 */
function webform_update_8004() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, '#autocomplete_options') !== FALSE) {
      $elements = str_replace('#autocomplete_options', '#autocomplete_items', $elements);
      $webform_config->set('elements', $elements);
      $webform_config->save(TRUE);
    }
  }
}

/**
 * Issue #2837090: Undefined function call webform_schema.
 */
function webform_update_8005() {
  // @see webform_update_8006() which fixes this broken hook.
}

/******************************************************************************/
// Webform-8.x-5.0-beta4 - December 26, 2016.
/******************************************************************************/

/**
 * Issue #2837090: Undefined function call webform_schema.
 */
function webform_update_8006() {
  // Fix key_value.collection which was no updated during the migration.
  $module_handler = \Drupal::moduleHandler();
  $database_type = Database::getConnection('default')->databaseType();
  if ($module_handler->moduleExists('webform') && !$module_handler->moduleExists('webform') && $database_type == 'mysql') {
    $database = \Drupal::database();

    $select = $database->select('key_value', 'kv');
    $select->fields('kv', ['collection', 'name', 'value']);
    $select->condition('collection', '%webform%', 'LIKE');
    $result = $select->execute();
    while ($record = $result->fetchAssoc()) {
      $old_collection = $record['collection'];
      $new_collection = str_replace('webform', 'webform', $record['collection']);

      $collection_select = $database->select('key_value', 'kv');
      $collection_select->fields('kv', ['collection', 'name', 'value']);
      $collection_select->condition('collection', $new_collection);
      $collection_result = $collection_select->execute();

      // Only insert the new record if there the collection does not exist.
      if (!$collection_result->fetchAll()) {
        $record['collection'] = $new_collection;
        $database->insert('key_value')
          ->fields(['collection', 'name', 'value'])
          ->values(array_values($record))
          ->execute();
      }

      // Delete the old record.
      $database->delete('key_value')
        ->condition('collection', $old_collection)
        ->execute();
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta5 - January 30, 2017.
/******************************************************************************/

/**
 * Issue #2840521: Add support for global CSS and JS.
 */
function webform_update_8007() {
  _webform_update_admin_settings();
}

/**
 * Issue #2839615: Disabling message about viewing user's previous submissions.
 */
function webform_update_8008() {
  _webform_update_webform_settings();
}

/**
 * Issue #2844020: Add admin and form specific setting to allow submit button to be clicked only once.
 */
function webform_update_8009() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2843400: Automated purging of submissions.
 */
function webform_update_8010() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2845028: Refactor and rework element formatting to better support multiple values.
 */
function webform_update_8011() {
  // Update admin.settings format to support
  // 'formats.{element_type}.item' and 'formats.{element_type}.items'.
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  $data = $admin_config->getRawData();
  if (!empty($data['format'])) {
    foreach ($data['format'] as $element_type => $element_format) {
      if (is_string($element_format)) {
        $data['format'][$element_type] = ['item' => $element_format];
      }
    }
    $admin_config->setData($data)->save();
  }

  // Update webform element to support #format_items.
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    // Get data.
    $data = $webform_config->getRawData();
    if (strpos($data['elements'], "'#format'") === FALSE) {
      continue;
    }

    $elements = Yaml::decode($data['elements']);
    _webform_update_8011($elements);

    $data['elements'] = Yaml::encode($elements);
    $webform_config->setData($data);
    $webform_config->save();
  }
}

/**
 * Move $element['#format'] to $element['#format_items'].
 *
 * Applies to ol, ul, comma, and semicolon.
 *
 * @param array $element
 *   A form element.
 */
function _webform_update_8011(array &$element) {
  if (isset($element['#format'])) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $webform_element = $element_manager->getElementInstance($element);

    $format = $element['#format'];
    $item_formats = $webform_element->getItemFormats();
    $items_formats = $webform_element->getItemsFormats();
    if (!isset($item_formats[$format]) && isset($items_formats[$format])) {
      unset($element['#format']);
      $element['#format_items'] = $format;
    }
  }

  foreach (Element::children($element) as $key) {
    if (is_array($element[$key])) {
      _webform_update_8011($element[$key]);
    }
  }
}

/**
 * Issue #2845776: Improve #multiple handling.
 */
function webform_update_8012() {
  _webform_update_admin_settings();
}

/**
 * Issue #2840858: Create Webform and Webform Submission Action plugins.
 */
function webform_update_8013() {
  _webform_update_actions();
}

/******************************************************************************/
// Webform-8.x-5.0-beta6 - February 13, 2017.
/******************************************************************************/

/**
 * Issue #2848042: Rework #type shorthand prefix handling.
 */
function webform_update_8014() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    // Get data, get elements, and update elements #type.
    $data = $webform_config->getRawData();
    $elements = Yaml::decode($data['elements']);
    // Make sure $elements has been decoded into an array.
    if (is_array($elements)) {
      _webform_update_8014($elements);

      // Set elements, set data, and save data.
      $data['elements'] = Yaml::encode($elements);
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Add 'webform_' prefix to #type.
 *
 * @param array $element
 *   A form element.
 */
function _webform_update_8014(array &$element) {
  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  // Add 'webform_' prefix to #type.
  if (isset($element['#type']) && !$element_manager->hasDefinition($element['#type']) && $element_manager->hasDefinition('webform_' . $element['#type'])) {
    $element['#type'] = 'webform_' . $element['#type'];
  }

  foreach (Element::children($element) as $key) {
    if (is_array($element[$key])) {
      _webform_update_8014($element[$key]);
    }
  }
}

/**
 * Issue #2850247: Experiment with system tray integration.
 */
function webform_update_8015() {
  _webform_update_admin_settings();
}

/**
 * Issue #2850455: Add lookup_keys to webform config entity. Flush cache entity definitions.
 */
function webform_update_8016() {
  drupal_flush_all_caches();
}

/**
 * Issue #2850455: Add lookup_keys to webform config entity. Update Webform lookup keys.
 */
function webform_update_8017() {
  // Must resave all Webform config lookup keys.
  // @see \Drupal\Core\Config\Entity\Query\QueryFactory::updateConfigKeyStore
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $webform->save();
  }
}

/**
 * Issue #2850885: Add ability to disable autocomplete for form and/or element.
 */
function webform_update_8018() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-beta7 - February 15, 2017(No updates required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta8 - March 5, 2017.
/******************************************************************************/

/**
 * Issue #2853302: Allow confirmation page title to be customized.
 */
function webform_update_8019() {
  _webform_update_webform_settings();
}

/**
 * Issue #2845724: Add webform opening and closing date/time.
 */
function webform_update_8020() {
  // Resave all webforms to convert status boolean to string.
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $webform->setStatus($webform->get('status'))->save();
  }
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-beta9 - March 5 2017 (No updates required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta10 - April 4, 2017.
/******************************************************************************/

/**
 * Issue #2858139: Add OptGroup support to WebformOption entity.
 */
function webform_update_8021() {
  _webform_update_options_settings();

  // Get WebformOptions category from config/install.
  $webform_options = WebformOptions::loadMultiple();
  $config_install_path = drupal_get_path('module', 'webform') . '/config/install';
  foreach ($webform_options as $id => $webform_option) {
    if (!$webform_option->get('category')) {
      if (file_exists("$config_install_path/webform.webform_options.$id.yml")) {
        $yaml = file_get_contents("$config_install_path/webform.webform_options.$id.yml");
        $data = Yaml::decode($yaml);
        $webform_option->set('category', $data['category']);
        $webform_option->save();
      }
    }
  }
}

/**
 * Issue #2858246: Enhance checkboxes and radios using iCheck.
 */
function webform_update_8022() {
  _webform_update_admin_settings();
}

/**
 * Issue #2854021: Send email based on element options selection.
 */
function webform_update_8023() {
  // Add *_options: [] to email handler settings.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    $has_email_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if ($handler['id'] === 'email') {
        $has_email_handler = TRUE;

        $settings = [];
        foreach ($handler['settings'] as $settings_key => $setting_value) {
          $settings[$settings_key] = $setting_value;
          if (preg_match('/_mail$/', $settings_key)) {
            $options_name = str_replace('_mail', '', $settings_key) . '_options';
            if (empty($handler['settings'][$options_name])) {
              $settings[str_replace('_mail', '', $settings_key) . '_options'] = [];
            }
          }
        }

        $handler['settings'] = $settings;
      }
    }

    if ($has_email_handler) {
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Issue #2861651: Add Opened and Closed Messages.
 */
function webform_update_8024() {
  // Change 'default_form_closed_message' to 'default_form_close_message' in
  // admin settings.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('default_form_close_message', $settings_config->get('default_form_closed_message'));
  $settings_config->clear('default_form_closed_message');
  $settings_config->save();
  _webform_update_admin_settings();

  // Change 'default_form_closed_message' to 'default_form_close_message' in
  // webform config.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.form_close_message', $webform_config->get('settings.form_closed_message'));
    $webform_config->clear('settings.form_closed_message');
    $webform_config->save();
  }
  _webform_update_webform_settings();
}

/**
 * Issue #2857417: Add support for open and close date/time to Webform nodes. Update database scheme.
 */
function webform_update_8025() {
  /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
  $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

  $webform_tables = $entity_reference_manager->getTableNames();
  $database_schema = \Drupal::database()->schema();
  $schema = \Drupal::keyValue('entity.storage_schema.sql')->getAll();
  foreach ($schema as $item_name => $item) {
    foreach ($item as $table_name => $table_schema) {
      foreach ($table_schema as $schema_key => $schema_data) {
        if ($schema_key == 'fields') {
          foreach ($schema_data as $field_name => $field_data) {
            $is_webform_field_status = (isset($webform_tables[$table_name]) && $field_name === $webform_tables[$table_name] . '_status');
            $is_webform_field_integer = ($field_data['type'] == 'int');
            if ($is_webform_field_status && $is_webform_field_integer) {
              $temp_field_name = $field_name . '_temp';

              // Add temp status field and copy value.
              $database_schema->addField($table_name, $temp_field_name, [
                'type' => 'varchar',
                'length' => 20,
              ]);
              \Drupal::database()
                ->query("UPDATE {" . $table_name . "} SET $temp_field_name = 'open' WHERE $field_name = 1")
                ->execute();
              \Drupal::database()
                ->query("UPDATE {" . $table_name . "} SET $temp_field_name = 'closed' WHERE $field_name <> 1")
                ->execute();

              // Drop, re-create, and restore status field.
              $schema[$item_name][$table_name]['fields'][$field_name] = [
                'description' => 'Flag to control whether this webform should be open, closed, or scheduled for new submissions.',
                'type' => 'varchar',
                'length' => 20,
              ];
              $database_schema->dropField($table_name, $field_name);
              $database_schema->addField($table_name, $field_name, $schema[$item_name][$table_name]['fields'][$field_name]);
              \Drupal::database()
                ->query("UPDATE {" . $table_name . "} SET $field_name = $temp_field_name")
                ->execute();

              // Drop temp field.
              $database_schema->dropField($table_name, $temp_field_name);

              // Add open and close.
              $states = ['open', 'close'];
              foreach ($states as $state) {
                $state_field_name = preg_replace('/_status$/', '_' . $state, $field_name);
                $schema[$item_name][$table_name]['fields'][$state_field_name] = [
                  'description' => "The $state date/time.",
                  'type' => 'varchar',
                  'length' => 20,
                ];
                $database_schema->addField($table_name, $state_field_name, $schema[$item_name][$table_name]['fields'][$state_field_name]);
              }
            }
          }
        }
      }
    }
  }
  \Drupal::keyValue('entity.storage_schema.sql')->setMultiple($schema);
}

/**
 * Issue #2857417: Add support for open and close date/time to Webform nodes. Update entity definitions.
 */
function webform_update_8026() {
  _webform_update_field_storage_definitions();
}

/**
 * Issue #2857417: Add support for open and close date/time to Webform nodes. Update field config settings.
 */
function webform_update_8027() {
  $field_configs = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['field_type' => 'webform']);
  foreach ($field_configs as $field) {
    $field->setSetting('status', $field->getSetting('status') ? WebformInterface::STATUS_OPEN : WebformInterface::STATUS_CLOSED);
    $field->setSetting('open', '');
    $field->setSetting('close', '');
    $field->save();
  }
}

/**
 * Issue #2859528: Add reply-to and return-path to email handler.
 */
function webform_update_8028() {
  // Add reply_to and return_path to Update admin settings.
  _webform_update_admin_settings();

  // Add reply_to and return_path to email handler settings.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    $has_email_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if ($handler['id'] === 'email') {
        $has_email_handler = TRUE;
        $handler['settings'] += [
          'reply_to' => '',
          'return_path' => '',
        ];
      }
    }

    if ($has_email_handler) {
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Issue #2856842: Allow emails to be sent to selected roles.
 */
function webform_update_8029() {
  _webform_update_admin_settings();
}

/**
 * Issue #2838423: Drafts for anonymous users.
 */
function webform_update_8030() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    $data['settings']['draft'] = ($data['settings']['draft'] == TRUE) ? WebformInterface::DRAFT_AUTHENTICATED : WebformInterface::DRAFT_NONE;
    $webform_config->setData($data)->save();
  }
}

/**
 * Issue #2854021: Send email based on element options selection.
 */
function webform_update_8031() {
  _webform_update_webform_handler_class_settings(EmailWebformHandler::class);
}

/**
 * Issue #2854020: Provide a mechanism to log submission transactions.
 */
function webform_update_8032() {
  _webform_update_webform_submission_storage_schema();

  if (!\Drupal::database()->schema()->tableExists('webform_submission_log')) {
    // Copied from:
    // \Drupal\webform\WebformSubmissionStorageSchema::getEntitySchema.
    $schema = [
      'description' => 'Table that contains logs of all webform submission events.',
      'fields' => [
        'lid' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique log event ID.',
        ],
        'webform_id' => [
          'description' => 'The webform id.',
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
        ],
        'sid' => [
          'description' => 'The webform submission id.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'handler_id' => [
          'description' => 'The webform handler id.',
          'type' => 'varchar',
          'length' => 64,
          'not null' => FALSE,
        ],
        'uid' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {users}.uid of the user who triggered the event.',
        ],
        'operation' => [
          'type' => 'varchar_ascii',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Type of operation, for example "save", "sent", or "update."',
        ],
        'message' => [
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Text of log message.',
        ],
        'data' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Serialized array of data.',
        ],
        'timestamp' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Unix timestamp of when event occurred.',
        ],
      ],
      'primary key' => ['lid'],
      'indexes' => [
        'webform_id' => ['webform_id'],
        'sid' => ['sid'],
        'uid' => ['uid'],
        'handler_id' => ['handler_id'],
        'handler_id_operation' => ['handler_id', 'operation'],
      ],
    ];

    \Drupal::database()->schema()->createTable('webform_submission_log', $schema);
  }
}

/**
 * Issue #2864851: Allow form builder to opt-in to converting anonymous drafts/submissions to authenticated drafts/submissions.
 */
function webform_update_8033() {
  _webform_update_webform_settings();
}

/**
 * Issue #2865353: Improve submission log integration.
 */
function webform_update_8034() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-beta11 April 6, 2017 (No update required).
/******************************************************************************/
