<?php

/**
 * @file
 * Archived Webform update hooks.
 */

use Drupal\Core\Path\Entity\PathAlias;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Uuid\Php as Uuid;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views\Entity\View;
use Drupal\webform\Element\WebformSignature as WebformSignatureElement;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Implements hook_update_dependencies().
 */
function webform_update_dependencies() {
  // Ensure that system_update_8501() runs before the webform update, so that
  // the new revision_default field is installed in the correct table.
  // @see https://www.drupal.org/project/webform/issues/2958102
  $dependencies['webform'][8099]['system'] = 8501;

  return $dependencies;
}

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
  _webform_update_string_replace('[webform-submission:', '[webform_submission:');
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
  // Issue #2863986: Allow updating modules with new service dependencies.
  \Drupal::service('kernel')->rebuildContainer();

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
  // Issue #2863986: Allow updating modules with new service dependencies.
  \Drupal::service('kernel')->rebuildContainer();

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
// Webform-8.x-5.0-beta7 - February 15, 2017 (No updates required).
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
// Webform-8.x-5.0-beta9 - March 5, 2017 (No updates required).
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
  // Issue #2863986: Allow updating modules with new service dependencies.
  \Drupal::service('kernel')->rebuildContainer();

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
  _webform_update_webform_handler_settings();
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
// Webform-8.x-5.0-beta11 - April 6, 2017 (No updates required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta12 - April 21, 2017.
/******************************************************************************/

/**
 * Issue #2867529: Email handler states setting should be index array.
 */
function webform_update_8035() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    $has_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if ($handler['id'] == 'email') {
        if (isset($handler['settings']['states']) && is_array($handler['settings']['states'])) {
          $handler['settings']['states'] = array_values(array_filter($handler['settings']['states']));
        }
      }
    }

    if ($has_handler) {
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Issue #2867855: Add category support to webform config entity.
 */
function webform_update_8036() {
  // Add an empty category to all webforms.
  _webform_update_webform_settings();

  // Must resave all Webform config lookup keys.
  // @see \Drupal\Core\Config\Entity\Query\QueryFactory::updateConfigKeyStore
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $title = $webform->get('title');
    if (preg_match('/^(Test: ([^:]+))/', $title, $match)) {
      $webform->set('category', $match[1]);
    }
    elseif (preg_match('/^Example:/', $title)) {
      $webform->set('category', 'Example');
    }
    elseif (preg_match('/^Demo:/', $title)) {
      $webform->set('category', 'Demo');
    }
    else {
      $webform->set('category', '');
    }
    $webform->save();
  }
}

/**
 * Issue #2868075: Token types are not defined but have tokens.
 */
function webform_update_8037() {
  \Drupal::token()->resetInfo();
}

/**
 * Issue #2870218: Improve External Library Management.
 */
function webform_update_8038() {
  _webform_update_admin_settings();
}

/**
 * Issue #2871215: Copied webform templates should not have dependencies.
 */
function webform_update_8039() {
  if (!\Drupal::moduleHandler()->moduleExists('webform_templates')) {
    return;
  }

  // Remove 'webform_templates' dependency copied to new forms.
  $config_install_directory = drupal_get_path('module', 'webform_templates') . '/config/install';
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (isset($data['dependencies']['enforced']['module']) && $data['dependencies']['enforced']['module'] == ['webform_templates']) {
      if (!file_exists("$config_install_directory/$webform_config_name.yml")) {
        unset($data['dependencies']['enforced']['module']);
        if (empty($data['dependencies']['enforced'])) {
          unset($data['dependencies']['enforced']);
        }
        $webform_config->setData($data);
        $webform_config->save();
      }
    }
  }
}

/**
 * Issue #286655: Add Quick Edit off canvas form.
 */
function webform_update_8040() {
  // Copied from: outside_in_install()
  Cache::invalidateTags(['rendered']);
  \Drupal::service('cache.discovery')->deleteAll();
}

/**
 * Issue #2871606: Add (optional) support for Chosen.
 */
function webform_update_8041() {
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $data = $config->getRawData();
  $data['libraries']['excluded_libraries'][] = 'jquery.chosen';
  $config->setData($data);
  $config->save();
}

/******************************************************************************/
// Webform-8.x-5.0-beta13 - May 31, 2017.
/******************************************************************************/

/**
 * Issue #2875371: Can't Add Email Handler w/Select "Send To".
 */
function webform_update_8042() {
  $settings = ['to_options', 'cc_options', 'bcc_options', 'from_options'];

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    $has_email_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if (in_array($handler['id'], ['email', 'scheduled_email'])) {
        foreach ($settings as $setting_name) {
          if (!empty($handler['settings'][$setting_name])) {
            $has_email_handler = TRUE;
            $handler['settings'][$setting_name] = WebformOptionsHelper::encodeConfig($handler['settings'][$setting_name]);
          }
        }
      }
    }
    if ($has_email_handler) {
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Issue #2874555: Add "How can we help you?" link to the Webform module admin pages.
 */
function webform_update_8043() {
  _webform_update_admin_settings();
}

/**
 * Issue #2872464: MultiStep Preview Page - change the Page Title and Progress Bar Title.
 */
function webform_update_8044() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2878307: webform example module disable causes a customised webform deleted.
 */
function webform_update_8045() {
  // Remove sub module dependencies copied to new forms.
  $webform_modules = WebformReflectionHelper::getSubModules();
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    // Don't remove dependencies for submodule config files.
    if (_webform_update_8045_is_submodule_config($webform_config_name)) {
      continue;
    }

    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();

    // Remove enforce module dependency when a sub-module's webform is
    // duplicated.
    if (isset($data['dependencies']['enforced']['module'])) {
      $data['dependencies']['enforced']['module'] = array_diff($data['dependencies']['enforced']['module'], $webform_modules);
      if (empty($data['dependencies']['enforced']['module'])) {
        unset($data['dependencies']['enforced']['module']);
        if (empty($data['dependencies']['enforced'])) {
          unset($data['dependencies']['enforced']);
        }
      }
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Check if config is from a Webform submodule.
 *
 * @param string $webform_config_name
 *   Webform config file name.
 *
 * @return bool
 *   TRUE if config is from a Webform submodule.
 */
function _webform_update_8045_is_submodule_config($webform_config_name) {
  $modules = WebformReflectionHelper::getSubModules();
  foreach ($modules as $module) {
    if (file_exists(drupal_get_path('module', $module) . "/config/install/$webform_config_name.yml")) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Issue #2878193: Allow actions (aka submit buttons) to be placed anywhere on a webform.
 */
function webform_update_8046() {
  // Change 'default_submit_button_label' to 'default_form_submit_label'.
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $data = $config->getRawData();
  if (!isset($data['settings']['default_submit_button_label']) && isset($data['settings']['default_form_submit_label'])) {
    $data['settings']['default_submit_button_label'] = $data['settings']['default_form_submit_label'];
    unset($data['settings']['default_submit_button_label']);
  }
  $config->setData($data);
  $config->save();
  _webform_update_admin_settings();

  // Update default (source) webform configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    $data = _webform_update_8046_convert_data($data);
    $webform_config->setData($data)->save();
  }

  // Update translated webform configuration.
  // ISSUE: Can't figure out the right way to update translated webform config.
  // WORKAROUND: Directly update config data stored in the database.
  $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
  $collections = [];
  foreach ($langcodes as $langcode) {
    $collections[] = "language.$langcode";
  }
  foreach (['config', 'config_snapshot'] as $table_name) {
    if (!\Drupal::database()->schema()->tableExists($table_name)) {
      continue;
    }
    $query = \Drupal::database()->select($table_name, 'c')
      ->fields('c', ['name', 'collection', 'data'])
      ->orderBy('name')
      ->orderBy('collection')
      ->condition('name', 'webform.webform.%', 'LIKE')
      ->condition('collection', $collections, 'IN');
    $result = $query->execute();
    while ($record = $result->fetchAssoc()) {
      $data = unserialize($record['data']);
      $data = _webform_update_8046_convert_data($data);
      \Drupal::database()->update($table_name)
        ->fields(['data' => serialize($data)])
        ->condition('collection', $record['collection'])
        ->condition('name', $record['name'])
        ->execute();
    }
  }
}

/**
 * Convert webform config data from settings.buttons to use the 'webform_actions' element.
 *
 * @param array $data
 *   Webform config data.
 *
 * @return array
 *   Webform config data with 'webform_actions' element.
 */
function _webform_update_8046_convert_data(array $data) {
  $button_names = [
    'submit',
    'draft',
    'wizard_prev',
    'wizard_next',
    'preview_prev',
    'preview_next',
  ];

  // Build actions element from webform's settings.
  $actions_element = [];
  $settings = $data['settings'];
  foreach ($button_names as $button_name) {
    $settings_prefix = ($button_name == 'submit') ? 'form_' . $button_name : $button_name . '_button';
    if (!empty($settings[$settings_prefix . '_label'])) {
      $actions_element['#' . $button_name . '__label'] = $settings[$settings_prefix . '_label'];
    }
    if (!empty($settings[$settings_prefix . '_attributes'])) {
      $actions_element['#' . $button_name . '__attributes'] = $settings[$settings_prefix . '_attributes'];
    }
    unset($settings[$settings_prefix . '_label']);
    unset($settings[$settings_prefix . '_attributes']);
  }
  $data['settings'] = $settings;

  // Append actions element to elements.
  if ($actions_element) {
    $elements = Yaml::decode($data['elements']);
    $elements['actions'] = [
      '#type' => 'webform_actions',
      '#title' => (string) t('Submit button(s)'),
    ] + $actions_element;
    $data['elements'] = Yaml::encode($elements);
  }

  return $data;
}

/**
 * Issue #2879217: Allow WebformHandlers and WebformExporters to be excluded.
 */
function webform_update_8047() {
  _webform_update_admin_settings();
}

/**
 * Issue #2879421: Cleanup webform.settings.
 */
function webform_update_8048() {
  _webform_update_admin_settings();

  $config = \Drupal::configFactory()->getEditable('webform.settings');

  // Change webform.settings.purge_settings to webform.settings.purge.
  if ($config->get('purge_settings')) {
    $config->set('purge', $config->get('purge_settings'));
    $config->clear('purge_settings');
  }

  // Change webform.config.elements to webform.settings.element.
  if ($config->get('elements')) {
    $config->set('element', $config->get('elements'));
    $config->clear('elements');
  }

  // Change webform.settings.element.exclude_types to
  // webform.settings.element.exclude_elements.
  if (($element = $config->get('element')) && isset($element['excluded_types'])) {
    $element['excluded_elements'] = $element['excluded_types'];
    unset($element['excluded_types']);
    $config->set('element', $element);
  }

  $config->save();
}

/**
 * Issue #2864843: Create a new computed_value element.
 */
function webform_update_8049() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, '#display_on') !== FALSE) {
      $elements = str_replace('#display_on: display', '#display_on: view', $elements);
      $webform_config->set('elements', $elements);
      $webform_config->save(TRUE);
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta14 - June 28, 2017.
/******************************************************************************/

/**
 * Issue #2856472: Allow multiple drafts per users.
 */
function webform_update_8050() {
  _webform_update_webform_settings();
}

/**
 * Issue #2885183: Add support for customized webform submission labels.
 */
function webform_update_8051() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2886173: Ability to have no empty option on a select element.
 */
function webform_update_8052() {
  _webform_update_admin_settings();
}

/**
 * Issue #2757491: Allow Webforms to be submitted using AJAX.
 */
function webform_update_8053() {
  _webform_update_webform_settings();
}

/**
 * Issue #2886853: Ability to customize user draft and submission columns.
 */
function webform_update_8054() {
  _webform_update_webform_settings();
}

/**
 * Issue #2887078: Allows preview page to customized.
 */
function webform_update_8055() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2887443: Default to and from email settings missing and not used.
 */
function webform_update_8056() {
  _webform_update_admin_settings();
}

/**
 * Issue #2854021: Send email based on element options selection.
 */
function webform_update_8057() {
  // Add ignore_access: FALSE to email handler settings.
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
          if ($settings_key == 'excluded_elements') {
            $settings['ignore_access'] = FALSE;
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
 * Issue #2888076: Redirect users to login page when trying to access a protected webform file.
 */
function webform_update_8058() {
  _webform_update_admin_settings();
}

/**
 * Issue #2888850: Validate source entity that passed as URL parameters.
 */
function webform_update_8059() {
  _webform_update_webform_settings();
}

/**
 * Issue #2888615: Allow preview to exclude elements.
 */
function webform_update_8060() {
  _webform_update_webform_settings();
}

/**
 * Issue #2871207: Random Multiple Submissions. Create webform table.
 */
function webform_update_8061() {
  // Issue #2923303: Problems with updating the webform module.
  if (\Drupal::database()->schema()->tableExists('webform')) {
    \Drupal::database()->schema()->dropTable('webform');
  }

  // Copied from: webform_schema().
  $schema = [
    'description' => 'Stores all webform data.',
    'fields' => [
      'webform_id' => [
        'description' => 'The webform id.',
        'type' => 'varchar',
        'length' => 32,
        'not null' => TRUE,
      ],
      'next_serial' => [
        'description' => 'The serial number to give to the next submission to this webform.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 1,
      ],
    ],
    'primary key' => ['webform_id'],
  ];
  \Drupal::database()->schema()->createTable('webform', $schema);
}

/**
 * Issue #2871207: Random Multiple Submissions. Populate next serial in webform table.
 */
function webform_update_8062() {
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    \Drupal::database()->insert('webform')
      ->fields(['webform_id', 'next_serial'])
      ->values([$webform->id(), $webform->getState('next_serial') ?: 1])
      ->execute();
    $webform->deleteState('next_serial');
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta15 - June 30, 2017.
/******************************************************************************/

/**
 * Issue #2891108: Recreating deleted webform with same name results in error.
 */
function webform_update_8063() {
  $webform_ids = array_keys(Webform::loadMultiple());
  if ($webform_ids) {
    \Drupal::database()->delete('webform_submission_log')
      ->condition('webform_id', $webform_ids, 'NOT IN')
      ->execute();
    \Drupal::database()->delete('webform')
      ->condition('webform_id', $webform_ids, 'NOT IN')
      ->execute();
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta16 - August 7, 2017.
/******************************************************************************/

/**
 * Issue #2878193: Allow actions (aka submit buttons) to be placed anywhere on a webform.
 */
function webform_update_8064() {
  // Move disabled HTML editor from ui to element.html_editor.
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $data = $config->getRawData();
  if (isset($data['ui']['html_editor_disabled'])) {
    $data['html_editor']['disabled'] = $data['ui']['html_editor_disabled'];
  }
  $config->setData($data);
  $config->save();

  _webform_update_admin_settings();
}

/**
 * Issue #2888717: Option to include empty components in [webform_submission:values] token.
 */
function webform_update_8065() {
  _webform_update_webform_settings();

  // Add exclude_empty: TRUE to email handler settings.
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
          if ($settings_key == 'ignore_access') {
            $settings['exclude_empty'] = TRUE;
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
 * Issue #2893111: Add permissions to form and element access controls.
 */
function webform_update_8066() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    foreach ($data['access'] as &$rules) {
      $rules['permissions'] = [];
    }
    $webform_config->setData($data)->save();
  }
}

/**
 * Issue #2893147: Allow empty element label to be customized.
 */
function webform_update_8067() {
  _webform_update_admin_settings();
}

/**
 * Issue #2896667: Add Reset button.
 */
function webform_update_8068() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2895140: Email handler field(s) convert special chars to HTML code.
 */
function webform_update_8069() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    $has_email_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if ($handler['id'] === 'email') {
        $has_email_handler = TRUE;
        foreach ($handler['settings'] as $settings_key => $setting_value) {
          if (in_array($settings_key, ['from_name', 'subject'])) {
            $handler['settings'][$settings_key] = preg_replace('/^\[([^]]+):value\]$/', '[\1:raw]', $setting_value);
          }
        }
      }
    }

    if ($has_email_handler) {
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Issue #2895809: Promote partnerships within the Webform module.
 */
function webform_update_8070() {
  _webform_update_admin_settings();
}

/**
 * Issue #2898424: Improve Remote Post.
 */
function webform_update_8071() {
  $settings_mapping = [
    'insert_url' => 'completed_url',
    'insert_custom_data' => 'completed_custom_data',
    'update_url' => 'updated_url',
    'update_custom_data' => 'updated_custom_data',
    'delete_url' => 'deleted_url',
    'delete_custom_data' => 'deleted_custom_data',
  ];

  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $has_remote_post = FALSE;
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof RemotePostWebformHandler) {
        $has_remote_post = TRUE;
        $configuration = $handler->getConfiguration();
        foreach ($configuration['settings'] as $key => $value) {
          if (isset($settings_mapping[$key])) {
            $configuration['settings'][$settings_mapping[$key]] = $value;
            unset($configuration['settings'][$key]);
          }
        }
        $configuration['settings'] += $handler->defaultConfiguration();
        $handler->setConfiguration($configuration);
      }
    }
    if ($has_remote_post) {
      $webform->save();
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta17 - September 10 2017.
/******************************************************************************/

/**
 * Issue #2901738: Add support conditions to WebformHandler.
 */
function webform_update_8072() {
  drupal_flush_all_caches();

  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      $configuration = $handler->getConfiguration();
      $handler->setConfiguration($configuration);
    }
    $webform->save();
  }
}

/**
 * Issue #2905955: Update hook webform_update_8048 is broken.
 */
function webform_update_8073() {
  webform_update_8048();
}

/**
 * Issue #2906292: Display element description as help text (tooltip).
 */
function webform_update_8074() {
  _webform_update_admin_settings();
}

/**
 * Issue #2895671: Entity reference format.
 */
function webform_update_8075() {
  // Update webform.settings.
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  if ($admin_config->get('export.entity_reference_format') === 'id') {
    $admin_config->set('export.entity_reference_items', ['id']);
  }
  else {
    $admin_config->set('export.entity_reference_items', ['id', 'title', 'url']);
  }
  $admin_config->clear('export.entity_reference_format');
  $admin_config->save();

  // Update webform results.export state.
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $namespace = 'webform.webform.' . $webform->id();
    $values = \Drupal::state()->get($namespace, []);
    if (empty($values)) {
      continue;
    }

    // Loop through state values looking for results.export variable.
    foreach ($values as $key => &$value) {
      if (strpos($key, 'results.export') === 0) {
        switch ($value['entity_reference_format']) {
          case 'id':
            $value['entity_reference_items'] = ['id'];
            break;

          default:
          case 'link':
            $value['entity_reference_items'] = ['id', 'title', 'url'];
            break;
        }
        unset($value['entity_reference_format']);
        \Drupal::state()->set($namespace, $values);
      }
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta18 - September 12, 2017.
/******************************************************************************/

/**
 * Issue #2908080: Allow options single and multiple format to be specified during export.
 */
function webform_update_8076() {
  // Update webform.settings.
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  if ($options_format = $admin_config->get('export.options_format')) {
    $admin_config->set('export.options_single_format', $options_format);
    $admin_config->set('export.options_multiple_format', $options_format);
  }
  $admin_config->clear('export.options_format');
  $admin_config->save();

  // Update webform results.export state.
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $namespace = 'webform.webform.' . $webform->id();
    $values = \Drupal::state()->get($namespace, []);
    if (empty($values)) {
      continue;
    }

    // Loop through state values looking for results.export variable.
    foreach ($values as $key => &$value) {
      if (strpos($key, 'results.export') === 0) {
        $value['options_single_format'] = $value['options_format'];
        $value['options_multiple_format'] = $value['options_format'];
        unset($value['options_format']);
        \Drupal::state()->set($namespace, $values);
      }
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta19 - September 28, 2017.
/******************************************************************************/

/**
 * Issue #2909723: Improve hook requirements.
 */
function webform_update_8077() {
  // Convert libraries.cdn to requirements.cdn.
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  $cdn = $admin_config->get('libraries.cdn') ?: FALSE;
  $admin_config->set('requirements.cdn', !$cdn);
  $admin_config->clear('libraries.cdn');
  $admin_config->save();

  _webform_update_admin_settings();
}

/**
 * Issue #2906792: Sender and Return-path headers are not used correctly.
 */
function webform_update_8078() {
  _webform_update_admin_settings();
  _webform_update_webform_handler_settings();
}

/**
 * Issue #2911329: Create 'About' tab.
 */
function webform_update_8079() {
  // Convert ui.help_menu_disabled to ui.contribute_disabled.
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  $help_menu_disabled = $admin_config->get('ui.help_menu_disabled') ?: FALSE;
  $admin_config->set('ui.contribute_disabled', $help_menu_disabled);
  $admin_config->clear('ui.help_menu_disabled');
  $admin_config->save();
}

/******************************************************************************/
// Webform-8.x-5.0-beta20 - October 30, 2017.
/******************************************************************************/

/**
 * Issue #2914153: Add 'More' hide/show text support to elements.
 */
function webform_update_8080() {
  _webform_update_admin_settings();
}

/**
 * Issue #2913215: Remote Post handler add GET method support.
 */
function webform_update_8081() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #2912672: Mismatch entity Webform submission caused by langcode changes.
 */
function webform_update_8082() {
  // Set webform_submission.langcode to NOT NULL.
  // Copied from: system_update_8007().
  $schema = \Drupal::keyValue('entity.storage_schema.sql')->getAll();
  $item = &$schema['webform_submission.field_schema_data.langcode'];
  foreach ($item as &$table_schema) {
    foreach ($table_schema['fields'] as &$field) {
      $field['not null'] = TRUE;
    }
  }
  \Drupal::keyValue('entity.storage_schema.sql')->setMultiple($schema);

  // Update field definition.
  // Copied from: node_update_8300().
  $spec = [
    'type' => 'varchar_ascii',
    'length' => 12,
    'not null' => TRUE,
  ];
  Database::getConnection()->schema()->changeField('webform_submission', 'langcode', 'langcode', $spec);
}

/**
 * Issue #2914904: Add section element.
 */
function webform_update_8083() {
  _webform_update_admin_settings();
}

/**
 * Issue #2917174: Improve "Webform Scheduled Email Handler".
 */
function webform_update_8084() {
  _webform_update_admin_settings();
}

/**
 * Issue #2908882: Loading next or previous page with AJAX should scroll to top of the window.
 */
function webform_update_8085() {
  _webform_update_webform_settings();
}

/**
 * Issue #2918860: Webform MultiStep URL Params.
 */
function webform_update_8086() {
  _webform_update_webform_settings();
}

/**
 * Issue #2918860: Webform MultiStep URL Params.
 */
function webform_update_8087() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (isset($data['settings']['wizard_complete'])) {
      $data['settings']['wizard_comfirmation'] = $data['settings']['wizard_complete'];
      unset($data['settings']['wizard_complete']);
    }
    if (isset($data['settings']['wizard_complete_label'])) {
      $data['settings']['wizard_comfirmation_label'] = $data['settings']['wizard_complete_label'];
      unset($data['settings']['wizard_complete_label']);
    }
    $webform_config->setData($data)->save();
  }
}

/******************************************************************************/
// Webform-8.x-5.0-beta21 - October 30, 2017 (No updates required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta22 - November 1, 2017.
/******************************************************************************/

/**
 * Issue #2919989: Add horizontal rule element.
 */
function webform_update_8088() {
  _webform_update_admin_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-beta23 - November 2, 2017 (No updates required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta24 - November 16, 2017 (No updates required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-beta25 - December 11, 2017.
/******************************************************************************/

/**
 * Issue #2920762: Typo in update 8087: "wizard_comfirmation_label".
 */
function webform_update_8089() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (isset($data['settings']['wizard_comfirmation'])) {
      $data['settings']['wizard_confirmation'] = $data['settings']['wizard_comfirmation'];
      unset($data['settings']['wizard_comfirmation']);
    }
    if (isset($data['settings']['wizard_comfirmation_label'])) {
      $data['settings']['wizard_confirmation_label'] = $data['settings']['wizard_comfirmation_label'];
      unset($data['settings']['wizard_comfirmation_label']);
    }
    $webform_config->setData($data)->save();
  }
}

/**
 * Issue #2920443: Browser Back Button to submit to previous wizard page.
 */
function webform_update_8090() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2923047: Redirect to user login when Access Denied for a Webform.
 */
function webform_update_8091() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2915653: Remote post error handling.
 */
function webform_update_8092() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2924551: Move the WebformComposite element to WebformCustomComposite.
 */
function webform_update_8093() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $config_name) {
    $config = $config_factory->getEditable($config_name);
    $elements = $config->get('elements');
    if (strpos($elements, "'#type': webform_composite") !== FALSE) {
      $elements = str_replace("'#type': webform_composite", "'#type': webform_custom_composite", $elements);
      $config->set('elements', $elements);
      $config->save(TRUE);
    }
  }
}

/**
 * Issue #2929665: Permit one webform submission per day based on email entered.
 */
function webform_update_8094() {
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc1 - December 25, 2017.
/******************************************************************************/

/**
 * Issue #2918721: Access controlling Webform administration.
 */
function webform_update_8095() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (!isset($data['access']['administer'])) {
      $data['access']['administer'] = [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ];
      $webform_config->setData($data)->save();
    }
  }
}

/**
 * Issue #2931888: Add a boolean flag 'use as likert' for options list and remove the machine name pattern matching in likert element.
 */
function webform_update_8096() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform_options.') as $config_name) {
    $config = $config_factory->getEditable($config_name);
    $data = $config->getRawData();

    $likert = (strpos($data['id'], 'likert_') === 0) ? TRUE : FALSE;
    WebformArrayHelper::insertAfter($data, 'category', 'likert', $likert);
    $config->setData($data);
    $config->save(TRUE);
  }
}

/**
 * Issue #2932607: Add Twig support to email body.
 */
function webform_update_8097() {
  _webform_update_webform_handler_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc2 - January 30, 2018.
/******************************************************************************/

/**
 * Issue #2933705: Element Range.
 */
function webform_update_8098() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, '#range__output') !== FALSE) {
      // Convert properties.
      $elements = str_replace(
        ['#range__output_prefix', '#range__output_suffix', '#range__output'],
        ['#output__field_prefix', '#output__field_suffix', '#output'],
        $elements);
      // Convert '#output': true to '#output': right.
      $elements = str_replace("'#output': true", "'#output': right", $elements);
      $webform_config->set('elements', $elements);
      $webform_config->save(TRUE);
    }
  }
}

/**
 * Issue #2888862: Provide a mechanism to lock a webform submission.
 */
function webform_update_8099() {
  // Copied from: node_update_8001()
  //
  // Install the definition that this field had in
  // \Drupal\webform\Entity\WebformSubmission::baseFieldDefinitions()
  // at the time that this update function was written. If/when code is
  // deployed that changes that definition, the corresponding module must
  // implement an update function that invokes
  // \Drupal::entityDefinitionUpdateManager()->updateFieldStorageDefinition()
  // with the new definition.
  $storage_definition = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Locked'))
    ->setDescription(t('A flag that indicates a locked webform submission.'))
    ->setDefaultValue(FALSE);

  \Drupal::entityDefinitionUpdateManager()
    ->installFieldStorageDefinition('locked', 'webform_submission', 'webform', $storage_definition);

  // Set default value.
  \Drupal::database()->update('webform_submission')
    ->fields(['locked' => 0])
    ->execute();

  // Add submission locked message to admin and webform settings.
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2888862: Provide a mechanism to lock a webform submission. Update handlers.
 */
function webform_update_8100() {
  // Add locked to action handler.
  _webform_update_webform_handler_settings();

  // Add locked to remote post handler's excluded data.
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $has_handler = FALSE;
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof RemotePostWebformHandler) {
        $has_handler = TRUE;
        $configuration = $handler->getConfiguration();
        $settings = $configuration['settings'];
        if ($settings['excluded_data']) {
          $settings['excluded_data']['locked'] = 'locked';
          $configuration['settings'] = $settings;
          $handler->setConfiguration($configuration);
        }
      }
    }
    if ($has_handler) {
      $webform->save();
    }
  }
}

/**
 * Issue #2935697: Add support for new Off-Canvas dialog tray. Remove quickedit links.
 */
function webform_update_8101() {
  \Drupal::service('cache.discovery')->deleteAll();
  Cache::invalidateTags(['rendered']);
}

/**
 * Issue #2939828: Allow site builder to display required indicator on all webforms or a specified webform.
 */
function webform_update_8102() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2939948: Date fields should respect #min and #max in test-mode. Remove hard-coded test date values.
 */
function webform_update_8103() {
  $test_date_elements = ['date', 'datetime', 'datelist'];
  $test_date_values = ['1942-06-18', '1940-07-07', '1943-02-25', '1940-10-09'];
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $data = $config->getRawData();
  $types = Yaml::decode($data['test']['types']);
  foreach ($test_date_elements as $test_date_element) {
    if (isset($types[$test_date_element]) && $types[$test_date_element] === $test_date_values) {
      unset($types[$test_date_element]);
    }
  }
  $data['test']['types'] = Yaml::encode($types);
  $config->setData($data);
  $config->save();
}

/**
 * Issue #2940490: Autofill webform with previous submission data.
 */
function webform_update_8104() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc3 - February 4, 2018.
/******************************************************************************/

/**
 * Issue #2941174 Notice: Undefined index: administer in WebformEntitySettingsAccessForm.
 */
function webform_update_8105() {
  // Re-execute update hook to make sure RC2 installs have the correct config.
  webform_update_8095();
}

/******************************************************************************/
// Webform-8.x-5.0-rc4 - March 13, 2018.
/******************************************************************************/

/**
 * Issue #2934970: Fully support for inline error form.
 */
function webform_update_8107() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #2944515: Report proper dependencies of a webform block.
 */
function webform_update_8108() {
  /** @var \Drupal\block\Entity\Block $block */
  foreach (\Drupal::entityTypeManager()->getStorage('block')->loadMultiple() as $block) {
    if ($block->getPluginId() == 'webform_block') {
      $webform_id = $block->getPlugin()->getConfiguration()['webform_id'];
      if (\Drupal::entityTypeManager()->getStorage('webform')->load($webform_id)) {
        $block->save();
      }
      else {
        // The webform does not exist. There is no point to keep this block.
        $block->delete();
      }
    }
  }
}

/**
 * Issue #2933909: Webform Image Select Element Improvements.
 */
function webform_update_8109() {
  $config_factory = \Drupal::configFactory();
  $install_webform_image_select = FALSE;
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'webform_image_select') !== FALSE) {
      $install_webform_image_select = TRUE;
      break;
    }
  }

  if ($install_webform_image_select && !\Drupal::moduleHandler()->moduleExists('webform_image_select')) {
    \Drupal::service('module_installer')->install(['webform_image_select']);
  }
}

/**
 * Issue #2947991: Disable the password field.
 */
function webform_update_8110() {
  $config_factory = \Drupal::configFactory();
  $has_password_element = FALSE;
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    // Check for password and password_confirm element #type.
    if (strpos($elements, "'#type': password") !== FALSE) {
      $has_password_element = TRUE;
      break;
    }
  }

  // If password element is not being used in any webform display both the
  // password and password_confirm element.
  if (!$has_password_element) {
    $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
    $excluded_elements = $admin_config->get('element.excluded_elements') ?: [];
    $excluded_elements['password'] = 'password';
    $excluded_elements['password_confirm'] = 'password_confirm';
    $admin_config->set('element.excluded_elements', $excluded_elements);
    $admin_config->save();
  }
}

/**
 * Issue #2947991: Disable the password field. Fix webform_update_8110().
 */
function webform_update_8111() {
  webform_update_8110();
}

/**
 * Issue #2951368: Provide dedicated 'test' webform operation and access rule.
 */
function webform_update_8112() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (!isset($data['access']['test'])) {
      $data['access']['test'] = [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ];
      $webform_config->setData($data)->save();
    }
  }
}

/**
 * Issue #2951001: Convert About section into Contribute section.
 */
function webform_update_8113() {
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $config->set('ui.contribute_disabled', $config->get('ui.about_disabled') ?: FALSE);
  $config->save();
}

/**
 * Issue #2951921: The contribute module is missing from the file system.
 */
function webform_update_8114() {
  // Skip if the Contribute module still exists.
  if (file_exists(drupal_get_path('module', 'contribute') . '/contribute.info.yml')) {
    return;
  }

  $contribute_config = \Drupal::configFactory()->getEditable('contribute.settings');

  // Migrate Contribute account info to Webform Contribute account info.
  \Drupal::configFactory()->getEditable('webform.settings')
    ->set('contribute.account_type', $contribute_config->get('account_type') ?: 'user')
    ->set('contribute.account_id', $contribute_config->get('account_id') ?: NULL)
    ->save();

  // Manually delete the Contribute module's configuration.
  $contribute_config->delete();

  // Manually remove the contribute module from core.extension configuration.
  $extension_config = \Drupal::configFactory()->getEditable('core.extension');
  $module = $extension_config->get('module');
  unset($module['contribute']);
  $extension_config->set('module', $module);
  $extension_config->save();

  // Remove schema definitions.
  \Drupal::database()->delete('key_value')
    ->condition('collection', 'system.schema')
    ->condition('name', 'contribute')
    ->execute();
}

/******************************************************************************/
// Webform-8.x-5.0-rc5 - March 14, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc6 - March 16, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc7 - March 23, 2018.
/******************************************************************************/

/**
 * Issue #2955218: Allow query and token to be removed from confirmation URL.
 */
function webform_update_8115() {
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc8 - April 2, 2018.
/******************************************************************************/

/**
 * Stop #multiple__label and #multiple__labels from being saved with every element.
 */
function webform_update_8116() {
  _webform_update_elements_clear_properties([
    '#multiple__label' => '',
    '#multiple__labels' => '',
  ]);
}

/**
 * Issue #2957192: Add postal_code to test data.
 */
function webform_update_8117() {
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $names = Yaml::decode($config->get('test.names'));
  $names += [
    'postal_code' => ['11111', '12345', '12345-6789'],
  ];
  $config->set('test.names', WebformYaml::encode($names));
  $config->save();
}

/******************************************************************************/
// Webform-8.x-5.0-rc9 - April 7, 2018.
/******************************************************************************/

/**
 * Issue #2957074: Invalid Tokens in Email Handler.
 */
function webform_update_8118() {
  _webform_update_string_replace('[webform-submission:', '[webform_submission:');
}

/**
 * Issue #2957002: Same webform multiple times on the same page.
 */
function webform_update_8119() {
  // Issue #2863986: Allow updating modules with new service dependencies.
  \Drupal::service('kernel')->rebuildContainer();

  // Issue #2982273: Route "webform.config.libraries" does not exist.
  \Drupal::service('router.builder')->rebuild();

  /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('webform.help_manager');
  $help_manager->addNotification('webform_update_8119',
    t("<strong>ATTENTION DEVELOPERS!!!</strong> The webform submission form's  BASE_FORM_ID and FORM_ID have changed.") .
    '<br/>' . t('Please make sure to update all webform related <code>hook_form_BASE_FORM_ID_alter()</code> and <code>hook_form_FORM_ID_alter()</code> hooks.') .
    ' ' . t('<a href=":href">Learn more</a>', [':href' => 'https://www.drupal.org/node/2959264']),
    'warning'
  );
}

/**
 * Issue #2953929: Remote handler does not display messages when HTTP status is different than 200.
 */
function webform_update_8120() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #2953929: Remote handler does not display messages when HTTP status is different than 200.
 */
function webform_update_8121() {
  _webform_update_webform_handler_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc10 - April 9, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc11 - April 20, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc12 - April 25 2018.
/******************************************************************************/

/**
 * Issue #2952419: Attached files are deleted without usage checking.
 */
function webform_update_8122() {
  _webform_update_admin_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc13 - June 4, 2018.
/******************************************************************************/

/**
 * Issue #2962442: Remove [webform-authenticated-user] token and use [current-user] token with clear value option.
 */
function webform_update_8123() {
  _webform_update_string_replace('[webform-authenticated-user:', '[current-user:');
}

/**
 * Issue #2971207: Hidden Field updated values not being captured on Submit.
 */
function webform_update_8124() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (strpos($data['elements'], "'#type': hidden") !== FALSE) {
      $elements = Yaml::decode($data['elements']);
      _webform_update_8124($elements);
      $data['elements'] = Yaml::encode($elements);

      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Recursively convert hidden elements #value to #default_value.
 *
 * @param array $element
 *   An element.
 */
function _webform_update_8124(array &$element) {
  if (isset($element['#type']) && $element['#type'] === 'hidden') {
    if (isset($element['#value']) && !isset($element['#default_value'])) {
      $element['#default_value'] = $element['#value'];
      unset($element['#value']);
    }
  }
  foreach (Element::children($element) as $key) {
    if (is_array($element[$key])) {
      _webform_update_8124($element[$key]);
    }
  }
}

/**
 * Issue #2966507: Start-to-finish documentation for showing Webforms in modals.
 */
function webform_update_8125() {
  _webform_update_admin_settings();
}

/**
 * Issue #2973377: Make the previously saved messages customizable.
 */
function webform_update_8126() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc14 - June 6, 2018.
/******************************************************************************/

/**
 * Issue #2974597: Enable default publishing status of new webforms.
 */
function webform_update_8127() {
  _webform_update_admin_settings();
}

/**
 * Issue #2932893: Filter out closed forms in webform field.
 */
function webform_update_8128() {
  _webform_update_webform_settings();
}

/**
 * Issue #2977378: Add 'exclude unselected checkboxes' from email notification and preview.
 */
function webform_update_8129() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
  _webform_update_webform_handler_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc15 - June 12, 2018.
/******************************************************************************/

/**
 * Issue #2974153: Non admin duplicate form.
 */
function webform_update_8130() {
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc16 - June 29, 2018.
/******************************************************************************/

/**
 * Issue #2980470: Convert email handler "default" settings to use "_default_" to prevent localization issues.
 */
function webform_update_8131() {
  // Get all webform handlers that are instances of the email handler.
  // This allows any custom handler that extends the EmailWebformHandler to be
  // updated.
  /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
  $handler_manager = \Drupal::service('plugin.manager.webform.handler');
  $definitions = $handler_manager->getDefinitions();
  $email_handler_ids = [];
  foreach ($definitions as $plugin_id => $definition) {
    if ($handler_manager->createInstance($plugin_id) instanceof EmailWebformHandler) {
      $email_handler_ids[$plugin_id] = $plugin_id;
    }
  }

  $default_settings = [
    'to_mail',
    'from_mail',
    'from_name',
    'subject',
    'body',
  ];

  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    // Get data.
    $data = $webform_config->getRawData();

    // Change 'default' to '_default' is email handler settings.
    $save_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if (!isset($email_handler_ids[$handler['id']])) {
        continue;
      }

      foreach ($handler['settings'] as $settings_key => $setting_value) {
        if ($setting_value === 'default' && in_array($settings_key, $default_settings)) {
          $handler['settings'][$settings_key] = EmailWebformHandler::DEFAULT_VALUE;
          $save_handler = TRUE;
        }
      }
    }

    if ($save_handler) {
      $webform_config->setData($data)->save();
    }
  }
}

/**
 * Issue #2980276: Webform assumes the /tmp directory is always the same, but if there are multiple servers, each may have its own /tmp directory.
 */
function webform_update_8132() {
  _webform_update_admin_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc17 - August 6, 2018.
/******************************************************************************/

/**
 * Issue #2890861: Webform toggle element is not accessible.
 */
function webform_update_8133() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    $yaml = Yaml::encode($data);
    if (strpos($yaml, 'webform_toggle')) {
      return;
    }
  }

  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings = $admin_config->getRawData();
  $settings['libraries']['excluded_libraries'][] = 'jquery.toggles';
  $admin_config->setData($settings)->save();
}

/**
 * Issue #2984348: Link to relevant wizard page on preview.
 */
function webform_update_8134() {
  _webform_update_admin_settings();
}

/**
 * Issue #2984868: Allow to specify the text format for emails.
 */
function webform_update_8135() {
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings = $admin_config->getRawData();
  $settings['html_editor']['element_format'] = $settings['html_editor']['format'];
  $settings['html_editor']['mail_format'] = $settings['html_editor']['format'];
  unset($settings['html_editor']['format']);
  $admin_config->setData($settings)->save();
}

/**
 * Issue #2987174: Replace and improve word/character counter.
 */
function webform_update_8136() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    if (strpos($data['elements'], "'#counter_message'") === FALSE) {
      continue;
    }

    $elements = Yaml::decode($data['elements']);
    _webform_update_8136($elements);

    $data['elements'] = Yaml::encode($elements);
    $webform_config->setData($data);
    $webform_config->save();
  }
}

/******************************************************************************/
// Webform-8.x-5.0-rc18 - August 7, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc19 - September 6, 2018.
/******************************************************************************/

/**
 * Update #counter_* attributes.
 *
 * Replace 'X' in counter message with '%d' and
 * move '#counter_message' to '#counter_maximum_message'.
 *
 * @param array $element
 *   A form element.
 */
function _webform_update_8136(array &$element) {
  if (isset($element['#counter_message'])) {
    if (strpos($element['#counter_message'], 'X') === FALSE) {
      $element['#counter_message'] = '%d ' . $element['#counter_message'];
    }
    else {
      $element['#counter_message'] = str_replace('X', '%d', $element['#counter_message']);
    }
    $element['#counter_maximum_message'] = $element['#counter_message'];
    unset($element['#counter_message']);
  }

  foreach (Element::children($element) as $key) {
    if (is_array($element[$key])) {
      _webform_update_8136($element[$key]);
    }
  }
}

/**
 * Issue #2983137: WYSIWYG editor not saving inline images for advanced html/text editor.
 */
function webform_update_8137() {
  _webform_update_admin_settings();
}

/**
 * Issue #2947303: Location element's geocomplete library is not supported.
 */
function webform_update_8138() {
  _webform_update_admin_settings();

  // Track if webform location geocomplete is being used.
  $has_geocomplete_element = FALSE;

  // Convert 'webform_location' to 'webform_location_geocomplete'.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $config_name) {
    $config = $config_factory->getEditable($config_name);
    $elements = $config->get('elements');
    if (strpos($elements, "'#type': webform_location") !== FALSE) {
      $elements = str_replace("'#type': webform_location", "'#type': webform_location_geocomplete", $elements);
      $config->set('elements', $elements);
      $config->save(TRUE);
      $has_geocomplete_element = TRUE;
    }
  }

  // Update exclude elements.
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  $excluded_elements = $admin_config->get('element.excluded_elements') ?: [];
  if (isset($excluded_elements['webform_location'])) {
    // Convert 'webform_location' to 'webform_location_geocomplete'.
    unset($excluded_elements['webform_location']);
    $excluded_elements['webform_location_geocomplete'] = 'webform_location_geocomplete';
  }
  elseif (!$has_geocomplete_element) {
    // If location geocomplete element is not being used then excluded it.
    $excluded_elements['webform_location_geocomplete'] = 'webform_location_geocomplete';
  }
  $admin_config->set('element.excluded_elements', $excluded_elements);
  $admin_config->save();
}

/**
 * Issue #2918669: Elements separated by conditional inside multistep wizard are both saved to submission.
 */
function webform_update_8139() {
  $build = [
    'list' => [
      '#theme' => 'item_list',
      '#title' => t('IMPORTANT! Elements, containers, and pages that are hidden using conditional logic will now have their submission data cleared when a webform is submitted.'),
      '#items' => [
        t('Please make sure to test any webform that contains conditionally hidden elements, containers, or pages.'),
        t('Any element that is conditionally hidden will have its submission data cleared.'),
        t("Existing submissions that have conditionally hidden elements will have the element's submission data cleared when the submission is updated."),
      ],
    ],
    'link' => [
      '#type' => 'link',
      '#title' => t('Learn more'),
      '#url' => Url::fromUri('https://www.drupal.org/node/2956589'),
    ],
  ];

  /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('webform.help_manager');
  $help_manager->addNotification(__FUNCTION__, $build, 'warning');
}

/**
 * Issue #2994411: Allow help to be hidden.
 */
function webform_update_8140() {
  _webform_update_admin_settings();
}

/**
 * Issue #2993327: Provide Default View to Override Submission Results Tab.
 */
function webform_update_8141() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();

  // Copied from: redirect_update_8103()
  $message = NULL;
  if (\Drupal::moduleHandler()->moduleExists('views') && !View::load('webform_submissions')) {
    $config_path = drupal_get_path('module', 'webform') . '/config/optional/views.view.webform_submissions.yml';
    $data = ['uuid' => (new Uuid())->generate()] + Yaml::decode(file_get_contents($config_path));
    \Drupal::configFactory()->getEditable('views.view.webform_submissions')->setData($data)->save(TRUE);
    $message = 'The new webform submissions view has been created.';
  }
  else {
    $message = 'Not creating a webform submissions view since it already exists.';
  }
  return $message;
}

/**
 * Issue #2995413: Allow email handler theme to be customized.
 */
function webform_update_8142() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #2943879: How to display alternate text when the user is not allowed to create a Webform?
 */
function webform_update_8143() {
  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');
  // Convert form login message to access denied message.
  if ($admin_config->get('settings.default_form_login_message') !== NULL) {
    $admin_config->set('settings.default_form_access_denied_message', $admin_config->get('settings.default_form_login_message'));
    $admin_config->clear('settings.default_form_login_message');
  }
  $admin_config->save();
  _webform_update_admin_settings();

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $config_name) {
    $config = $config_factory->getEditable($config_name);
    // Convert form login to access denied.
    if ($config->get('settings.form_login') !== NULL) {
      $config->set('settings.form_access_denied', $config->get('settings.form_login') ? WebformInterface::ACCESS_DENIED_LOGIN : WebformInterface::ACCESS_DENIED_DEFAULT);
      $config->clear('settings.form_login');
    }
    // Convert form login message to access denied message.
    if ($config->get('settings.form_login_message') !== NULL) {
      $config->set('settings.form_access_denied_message', $config->get('settings.form_login_message'));
      $config->clear('settings.form_login_message');
    }

    // Convert submission login to access denied.
    if ($config->get('settings.submission_login') !== NULL) {
      $config->set('settings.submission_access_denied', $config->get('settings.submission_login') ? WebformInterface::ACCESS_DENIED_LOGIN : WebformInterface::ACCESS_DENIED_DEFAULT);
      $config->clear('settings.submission_login');
    }
    // Convert submission login message to access denied message.
    if ($config->get('settings.submission_login_message') !== NULL) {
      $config->set('settings.submission_access_denied_message', $config->get('settings.submission_login_message'));
      $config->clear('settings.submission_login_message');
    }

    $config->save();
  }
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc20 - September 7, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc21 - September 8, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc22.
/******************************************************************************/

/**
 * Issue #2998239: Swift Mailer no longer working after custom email handler theme option was added.
 */
function webform_update_8144() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $data = $webform_config->getRawData();
    $has_email_handler = FALSE;
    foreach ($data['handlers'] as &$handler) {
      if (in_array($handler['id'], ['email', 'scheduled_email'])) {
        // Change 'theme' setting to 'theme_name' to prevent conflicts with
        // Swift Mailer.
        if (isset($handler['settings']['theme'])) {
          $handler['settings']['theme_name'] = $handler['settings']['theme'];
          unset($handler['settings']['theme']);
          $has_email_handler = TRUE;
        }
      }
    }
    if ($has_email_handler) {
      $webform_config->setData($data);
      $webform_config->save();
    }
  }
}

/**
 * Issue #3002183: Upload Total Limit instead of Upload File limit.
 */
function webform_update_8145() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #3000542: Remove webform_devel.module logger debugging.
 */
function webform_update_8146() {
  if ($config = \Drupal::configFactory()->getEditable('webform_devel.settings')) {
    $config->delete();
  }
}

/******************************************************************************/
// Webform-8.x-5.0-rc23 - October 20, 2018.
/******************************************************************************/

/**
 * Issue #3006468: Hide empty fields on on submission page.
 */
function webform_update_8147() {
  _webform_update_webform_settings();
}

/**
 * Issue #3007215: Option to retain webform title when source entity is provided.
 */
function webform_update_8148() {
  _webform_update_webform_settings();
}

/**
 * Issue #3007247: Custom composite fields appear as block elements.
 */
function webform_update_8149() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, "'add_more'") !== FALSE) {
      $elements = str_replace("'#add_more'", "'#add_more_items'", $elements);
      $elements = str_replace("'#multiple__add_more'", "'#multiple__add_more_items'", $elements);
      $webform_config->set('elements', $elements);
      $webform_config->save(TRUE);
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-rc24 - October 22, 2018.
/******************************************************************************/

/**
 * Issue #2980032: SUBMISSION BEHAVIORS: Allow edit the previous submission.
 */
function webform_update_8150() {
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc25 - November 5, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc26 - November 5, 2018 (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc27 - November 28, 2018.
/******************************************************************************/

/**
 * Issue #3013767: Computed twig element is not working on multi-step form.
 */
function webform_update_8151() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');

    // Try to decode elements.
    try {
      $elements = Yaml::decode($elements);
    }
    catch (\Exception $exception) {
      continue;
    }

    // Make sure elements is an array.
    if (!is_array($elements)) {
      continue;
    }

    $has_computed_element = FALSE;
    $flattened_elements =& WebformFormHelper::flattenElements($elements);
    foreach ($flattened_elements as &$element) {
      // Convert #value property to #template property.
      // @see \Drupal\webform\Entity\Webform::initElementsRecursive
      if (isset($element['#type']) && strpos($element['#type'], 'webform_computed_') === 0) {
        $has_computed_element = TRUE;
        if (isset($element['#value']) && !isset($element['#template'])) {
          $element['#template'] = $element['#value'];
          unset($element['#value']);
        }
      }
    }
    if ($has_computed_element) {
      $webform_config->set('elements', Yaml::encode($elements));
      $webform_config->save(TRUE);
    }
  }
}

/**
 * Issue #3014933: Webform paths not being removed when a webform is deleted.
 */
function webform_update_8153() {
  // Load all webforms to improve performance.
  $webform = Webform::loadMultiple();

  if (\Drupal::entityTypeManager()->hasDefinition('path_alias')) {
    $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $ids = $storage->getQuery()
      ->condition('path', '/webform/', 'STARTS_WITH')
      ->execute();

    /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
    foreach ($storage->loadMultiple($ids) as $path_alias) {
      if (preg_match('#^/webform/([^/]+)/(?:drafts|submissions)$#', $path_alias->getPath(), $match)) {
        // Check if the webform still exists.
        $webform_id = $match[1];
        if (!isset($webform[$webform_id])) {
          $path_alias->delete();
        }
      }
    }
  }
  else {
    $database = \Drupal::database();
    $select = $database->select('url_alias', 'u');
    $select->fields('u', ['pid', 'source', 'alias', 'langcode']);
    $select->condition('source', '/webform/%', 'LIKE');
    $result = $select->execute();
    while ($record = $result->fetchAssoc()) {
      if (preg_match('#^/webform/([^/]+)/(?:drafts|submissions)$#', $record['source'], $match)) {
        // Check if the webform still exists.
        $webform_id = $match[1];
        if (!isset($webform[$webform_id])) {
          // Delete the broken URL alias.
          $database->delete('url_alias')
            ->condition('pid', $record['pid'])
            ->execute();
        }
      }
    }
  }
}

/**
 * Issue #3015990: Option not to store the IP address for logged-in users.
 */
function webform_update_8154() {
  _webform_update_webform_settings();
}

/******************************************************************************/
// Webform-8.x-5.0-rc28 - December 7, 2018
/******************************************************************************/

/**
 * Issue #3017679: 2 different validation range modes for date/time field.
 */
function webform_update_8155() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');

    // Try to decode elements.
    try {
      $elements = Yaml::decode($elements);
    }
    catch (\Exception $exception) {
      continue;
    }

    // Make sure elements is an array.
    if (!is_array($elements)) {
      continue;
    }

    $has_date_element = FALSE;
    $flattened_elements =& WebformFormHelper::flattenElements($elements);
    foreach ($flattened_elements as &$element) {
      // Convert #min/max property to #date_date_(min|max) property.
      // @see \Drupal\webform\Entity\Webform::initElementsRecursive
      if (isset($element['#type']) && in_array($element['#type'], ['date', 'datetime', 'datelist'])) {
        $has_date_element = TRUE;
        if (isset($element['#min']) && !isset($element['#date_date_min'])) {
          $element['#date_date_min'] = $element['#min'];
          unset($element['#min']);
        }
        if (isset($element['#max']) && !isset($element['#date_date_max'])) {
          $element['#date_date_max'] = $element['#max'];
          unset($element['#max']);
        }
      }
    }
    if ($has_date_element) {
      $webform_config->set('elements', Yaml::encode($elements));
      $webform_config->save(TRUE);
    }
  }
}

/******************************************************************************/
// Webform-8.x-5.0-rc29 - December 7, 2018. (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0-rc30 - December 16, 2018.
/******************************************************************************/

/**
 * Issue #3015180: Add 'webform_submission_log' submodule.
 */
function webform_update_8156() {
  $enable = \Drupal::config('webform.settings')->get('settings.default_submission_log');
  if (!$enable) {
    $query = \Drupal::entityQuery('webform')
      ->condition('settings.submission_log', TRUE)
      ->count();
    $enable = $query->execute() > 0;
  }

  if ($enable) {
    try {
      \Drupal::service('module_installer')->install(['webform_submission_log']);
    }
    catch (\Drupal\Core\Database\SchemaObjectExistsException $exception) {
      // This is actually expected. The table {webform_submission_log} would exist
      // from webform submission entity schema.
    }

    // Because MySQL does not allow default value on blob/text column types and
    // we want to add a not null blob column to a table that is likely to have
    // existing rows, we are doing it in such a 3-step fashion.
    \Drupal::database()->schema()->addField('webform_submission_log', 'variables', [
      'type' => 'blob',
      'size' => 'big',
      'description' => 'Serialized array of variables that match the message string and that is passed into the t() function.',
    ]);

    \Drupal::database()->update('webform_submission_log')->fields([
      'variables' => serialize([]),
    ])->execute();

    \Drupal::database()->schema()->changeField('webform_submission_log', 'variables', 'variables', [
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
      'description' => 'Serialized array of variables that match the message string and that is passed into the t() function.',
    ]);
  }
}

/******************************************************************************/
// Webform-8.x-5.0-rc31 - December 31, 2018. (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.0 - December 24, 2018. (No update required).
/******************************************************************************/

/******************************************************************************/
// Webform-8.x-5.1 - January 2, 2019.
/******************************************************************************/

/**
 * Issue #3022398: Possible modification to update hook and/or documentation.
 */
function webform_update_8157() {
  _webform_update_webform_submission_storage_schema();
}

/******************************************************************************/
// Webform-8.x-5.2 - TDB.
/******************************************************************************/

/**
 * Issue #3023863: Typo in State/Province codes options.
 */
function webform_update_8158() {
  /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
  $webform_options = WebformOptions::load('state_province_codes');
  if (!$webform_options) {
    return;
  }
  $options = $webform_options->getOptions();
  if (isset($options['AE']) && $options['AE'] === 'Armed Forces (Canada, Europe, Africa, or Middle East') {
    $options['AE'] = 'Armed Forces (Canada, Europe, Africa, or Middle East)';
    $webform_options->setOptions($options)->save();
  }
}

/**
 * Issue #3023223: Remove unused fields settings.
 */
function webform_update_8159() {
  /** @var \Drupal\field\FieldStorageConfigInterface[] $field_storage_configs */
  $field_storage_configs = FieldStorageConfig::loadMultiple();
  foreach ($field_storage_configs as $field_storage_config) {
    if ($field_storage_config->getType() !== 'webform') {
      continue;
    }

    list($entity_type, $field_name) = explode('.', $field_storage_config->getConfigTarget());
    $bundles = $field_storage_config->getBundles();
    foreach ($bundles as $bundle) {
      $config = \Drupal::configFactory()
        ->getEditable("field.field.$entity_type.$bundle.$field_name");
      $data = $config->getRawData();
      if (isset($data['settings']['default_data'])) {
        unset(
          $data['settings']['default_data'],
          $data['settings']['status'],
          $data['settings']['open'],
          $data['settings']['close']
        );
        $config->setData($data)->save();
      }
    }
  }
}

/**
 * Issue #3019987: Fatal error: Allowed memory size after updating to rc29.
 */
function webform_update_8160() {
  $config = \Drupal::configFactory()
    ->getEditable('webform.webform.example_computed_elements');
  if (!$config) {
    return;
  }

  // Remove nested calculation elements.
  $elements = WebformYaml::decode($config->get('elements'));
  if (isset($elements['calculation']) && isset($elements['calculation']['calculation'])) {
    $path = drupal_get_path('module', 'webform') . '/modules/webform_examples/config/install/webform.webform.example_computed_elements.yml';
    $data = Yaml::decode(file_get_contents($path));
    $config->set('elements', $data['elements'])->save();
  }
}

/**
 * Issue #3026068: Ensure the webform submission has the langcode entity key set.
 */
function webform_update_8161() {
  // Installations that were updated from 8.x-5.0-beta10 or older might have a
  // unclean langcode definition, update it. First make sure that the langcode
  // entity key is set, if not, set it and update the field definition.
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $entity_definition_update_manager->getEntityType('webform_submission');
  if (!$entity_type->getKey('langcode')) {
    $keys = $entity_type->getKeys();
    $keys['langcode'] = 'langcode';
    $entity_type->set('entity_keys', $keys);
    $entity_definition_update_manager->updateEntityType($entity_type);

    $langcode_field = BaseFieldDefinition::create('language')
      ->setName('langcode')
      ->setTargetEntityTypeId('webform_submission')
      ->setLabel(t('Language'))
      ->setDescription(t('The submission language code.'));
    \Drupal::entityDefinitionUpdateManager()->updateFieldStorageDefinition($langcode_field);
  }
}

/**
 * Issue #3035054: Captcha challenge still visible after 'close time'.
 */
function webform_update_8162() {
  \Drupal::configFactory()
    ->getEditable('webform.settings')
    ->set('third_party_settings.captcha', [
      'replace_administration_mode' => TRUE,
    ])
    ->save();
}

/**
 * Issue #2902977: Provide tool for importing submissions from a CSV document.
 */
function webform_update_8163() {
  _webform_update_admin_settings();
}

/**
 * Issue #3050884: Allow draft(s) previous message to be customized.
 */
function webform_update_8164() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #3053420: Allows Ajax visual effects to be customized.
 */
function webform_update_8165() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #3049023: All Webforms are listed on Content languages form.
 */
function webform_update_8166() {
  _webform_update_webform_submission_translation();
}

/**
 * Issue #3026111: Create a logo and header for the Webform module.
 */
function webform_update_8167() {
  \Drupal::configFactory()->getEditable('webform.settings')
    ->clear('ui.contribute_disabled')
    ->clear('contribute')
    ->save();
}

/**
 * Issue #3033726: Provide option to redirect to a URL if Remote Post fails.
 */
function webform_update_8168() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #3062308: Add the ability to use admin theme for a webform's canonical route.
 */
function webform_update_8169() {
  _webform_update_webform_settings();
}

/**
 * Issue #3064070: Split STATE_DRAFT into STATE_DRAFT_CREATED and STATE_DRAFT_UPDATE.
 */
function webform_update_8170() {
  // List of handler is that use webform submission state.
  $states_handler_ids = ['action', 'email', 'scheduled_email', 'remote_post'];

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    $handler_updated = FALSE;
    foreach ($data['handlers'] as &$handler) {
      $settings = $handler['settings'];
      if (!in_array($handler['id'], $states_handler_ids)) {
        continue;
      }

      if (isset($settings['states'])
        && is_array($settings['states'])
        && in_array('draft', $settings['states'])) {
        // Remove old 'draft' state.
        WebformArrayHelper::removeValue($settings['states'], 'draft');
        // Prepend new 'draft_created' and 'draft_updated' states.
        $settings['states'] = array_merge([
          'draft_created',
          'draft_updated',
        ], $settings['states']);
      }

      if ($handler['id'] === 'remote_post'
        && isset($settings['draft_url'])) {
        $settings['draft_created_url'] = $settings['draft_url'];
        $settings['draft_created_custom_data'] = $settings['draft_custom_data'];
        $settings['draft_updated_url'] = $settings['draft_url'];
        $settings['draft_updated_custom_data'] = $settings['draft_custom_data'];
        unset($settings['draft_url']);
        unset($settings['draft_custom_data']);
      }

      if ($handler['settings'] != $settings) {
        $handler_updated = TRUE;
        $handler['settings'] = $settings;
      }
    }

    if ($handler_updated) {
      $webform_config->setData($data)->save();
    }
  }
}

/**
 * Issue #3066994: Form elements keys (machine name) to be in all cases not just lowercase.
 */
function webform_update_8171() {
  _webform_update_admin_settings();
}

/**
 * Issue #3073995: Setting to enable ajax on all webforms.
 */
function webform_update_8172() {
  _webform_update_admin_settings();
}

/**
 * Issue #3076086: Allow confirm email to use flexbox.
 */
function webform_update_8173() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (strpos($data['elements'], 'webform_flexbox') !== FALSE
      && strpos($data['elements'], 'webform_email_confirm') !== FALSE
    ) {
      $elements = WebformYaml::decode($data['elements']);
      _webform_update_8173($elements);
      $data['elements'] = WebformYaml::encode($elements);
      $webform_config->setData($data)->save();
    }
  }
}

/**
 * Recursively add flexbox: '0' to email confirm elements.
 *
 * @param array $element
 *   An element.
 */
function _webform_update_8173(array &$element) {
  if (isset($element['#type'])
    && $element['#type'] === 'webform_email_confirm'
    && !isset($element['#flexbox'])) {
    $element['#flexbox'] = '0';
  }
  foreach (Element::children($element) as $key) {
    if (is_array($element[$key])) {
      _webform_update_8173($element[$key]);
    }
  }
}

/**
 * Issue #3084531: Allow Email handler add additional parameters.
 */
function webform_update_8174() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #3087865: Webform email handler default subject is missing title.
 */
function webform_update_8175() {
  // Update translated webform.settings configuration.
  foreach (['config', 'config_snapshot'] as $table_name) {
    if (!\Drupal::database()->schema()->tableExists($table_name)) {
      continue;
    }
    $query = \Drupal::database()->select($table_name, 'c')
      ->fields('c', ['name', 'collection', 'data'])
      ->condition('name', 'webform.settings');
    $result = $query->execute();
    while ($record = $result->fetchAssoc()) {
      $data = unserialize($record['data']);
      $data['mail']['default_subject'] = str_replace('[webform_submission:source-entity]', '[webform_submission:source-title]', $data['mail']['default_subject']);
      \Drupal::database()->update($table_name)
        ->fields(['data' => serialize($data)])
        ->condition('collection', $record['collection'])
        ->condition('name', $record['name'])
        ->execute();
    }
  }
}

/**
 * Issue #3086507: Allow wizard pages / bar to be updated based on states API.
 */
function webform_update_8176() {
  _webform_update_webform_settings();
}

/**
 * Issue #3082822: Anonymous role view submission via secure token.
 */
function webform_update_8177() {
  _webform_update_webform_settings();
}

/**
 * Issue #3095275: Allow webform management filter by default category and states to be customized
 */
function webform_update_8178() {
  _webform_update_admin_settings();
}

/**
 * Issue #3101300: Allow 'Save' (update) action label to be customized.
 */
function webform_update_8179() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');

    // Try to decode elements.
    try {
      $elements = Yaml::decode($elements);
    }
    catch (\Exception $exception) {
      continue;
    }

    // Make sure elements is an array.
    if (!is_array($elements)) {
      continue;
    }

    // Copy submit attributes to update attributes to prevent any
    // unexpected regressions.
    $has_actions_element = FALSE;
    $flattened_elements =& WebformFormHelper::flattenElements($elements);
    foreach ($flattened_elements as &$element) {
      if (!isset($element['#type']) || $element['#type'] !== 'webform_actions') {
        continue;
      }

      if (!empty($element['#submit__attributes']) && empty($element['#update__attributes'])) {
        $element['#update__attributes'] = $element['#submit__attributes'];
        $has_actions_element = TRUE;
      }
    }
    if ($has_actions_element) {
      $webform_config->set('elements', Yaml::encode($elements));
      $webform_config->save(TRUE);
    }
  }
}

/**
 * Issue #3103171: Move deprecated jQuery UI buttons element into a sub-module.
 */
function webform_update_8180() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'webform_buttons') !== FALSE) {
      // Enable the webform_jqueryui_buttons.module.
      \Drupal::service('module_installer')
        ->install(['webform_jqueryui_buttons']);
      return;
    }
  }
}

/**
 * Issue #3103032: Move deprecated location geocomplete element into a sub-module
 */
function webform_update_8181() {
  $config_factory = \Drupal::configFactory();
  $install_webform_location_geocomplete = FALSE;
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'webform_location_geocomplete') !== FALSE) {
      $install_webform_location_geocomplete = TRUE;
      break;
    }
  }

  // Install and configure the webform_location_geocomplete.module.
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  if ($install_webform_location_geocomplete) {
    // Install the webform_location_geocomplete.module.
    \Drupal::service('module_installer')
      ->install(['webform_location_geocomplete']);

    // Move the default Google maps API key.
    $api_key = $config->get('element.default_google_maps_api_key');
    if ($api_key) {
      $config->clear('element.default_google_maps_api_key');
      $config->set('third_party_settings.webform_location_geocomplete.default_google_maps_api_key', $api_key);
      $config->save();
    }
  }

  // Remove 'webform_location_geocomplete' from excluded elements.
  $config->clear('element.excluded_elements.webform_location_geocomplete');
}

/**
 * Issue #3102996: Move deprecated toggles elements into a sub-module.
 */
function webform_update_8182() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'webform_toggle') !== FALSE) {
      // Enable the webform_toggles.module.
      \Drupal::service('module_installer')
        ->install(['webform_toggles']);
      return;
    }
  }
}

/**
 * Issue #3103571: Move deprecated icheck support into a dedicated sub-module.
 */
function webform_update_8183() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'icheck') !== FALSE) {
      // Enable the webform_icheck.module.
      \Drupal::service('module_installer')
        ->install(['webform_icheck']);
      return;
    }
  }
}

/**
 * Issue #3094270: Add webform variants support.
 */
function webform_update_8184() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #3104049: Exclude attached files from email submission values.
 */
function webform_update_8185() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #3108150: Save numeric properties as numbers instead of strings.
 */
function webform_update_8186() {
  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $elements = $webform_config->get('elements');

    // Try to decode elements.
    try {
      $elements = Yaml::decode($elements);
    }
    catch (\Exception $exception) {
      continue;
    }

    // Make sure elements is an array.
    if (!is_array($elements)) {
      continue;
    }

    $has_numeric_element_property = FALSE;
    $flattened_elements =& WebformFormHelper::flattenElements($elements);
    foreach ($flattened_elements as &$element) {
      $webform_element = $element_manager->getElementInstance($element);
      $default_properties = WebformArrayHelper::addPrefix($webform_element->getDefaultProperties());
      foreach ($element as $property_name => $property_value) {
        // Make sure the default properties exists.
        if (!array_key_exists($property_name, $default_properties)) {
          continue;
        }

        // See if the default is NULL or numeric.
        $default_value = $default_properties[$property_name];
        if (!is_null($default_value) && !is_numeric($default_value)) {
          continue;
        }

        $cast_value = ($property_value == (int) $property_value) ? (int) $property_value : (float) $property_value;
        if ($property_value == $cast_value) {
          $element[$property_name] = $cast_value;
          $has_numeric_element_property = TRUE;
        }
      }
    }

    if ($has_numeric_element_property) {
      $webform_config->set('elements', Yaml::encode($elements));
      $webform_config->save(TRUE);
    }
  }
}

/**
 * Issue #3108266: Update external libraries. Display warning about InputMask.
 */
function webform_update_8187() {
  if (!file_exists(\Drupal::root() . '/libraries/jquery.inputmask/dist/jquery.inputmask.min.js')) {
    /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
    $help_manager = \Drupal::service('webform.help_manager');
    $help_manager->addNotification('webform_update_8187',
      t('<strong>ATTENTION DEVELOPERS!!!</strong> Please make sure to download and update the <a href=":href">Input Mask</a> library to version 5.0.x', [':href' => 'https://github.com/RobinHerbots/Inputmask']) .
      ' ' . t('<a href=":href">Learn more about updating the Webform module\'s external libraries</a>', [':href' => 'https://www.drupal.org/docs/8/modules/webform/webform-libraries']),
      'warning'
    );
  }
}

/**
 * Issue #3097101: Remote Post cast boolean and numer values.
 */
function webform_update_8188() {
  _webform_update_webform_handler_settings();
}

/**
 * Issue #3108433: Allow users to personalize the submission list.
 */
function webform_update_8189() {
  _webform_update_admin_settings();
  _webform_update_webform_settings();
}

/**
 * Issue #3121814: Webform submission check taking a long time.
 */
function webform_update_8191() {
  // Copied from: history_update_8101()
  // @see https://www.drupal.org/docs/8/api/update-api/updating-database-schema-andor-data-in-drupal-8
  $spec = [
    'description' => 'The base table for webform_submission entities.',
    'fields' => [
      'sid' => [
        'type' => 'serial',
        'unsigned' => FALSE,
        'size' => 'normal',
        'not null' => TRUE,
      ],
      'webform_id' => [
        'description' => 'The ID of the target entity.',
        'type' => 'varchar_ascii',
        'length' => 32,
        'not null' => TRUE,
      ],
      'uuid' => [
        'type' => 'varchar_ascii',
        'length' => 128,
        'binary' => FALSE,
        'not null' => TRUE,
      ],
      'langcode' => [
        'type' => 'varchar_ascii',
        'length' => 12,
        'not null' => TRUE,
      ],
      'serial' => [
        'type' => 'int',
        'unsigned' => FALSE,
        'size' => 'normal',
        'not null' => FALSE,
      ],
      'token' => [
        'type' => 'varchar',
        'length' => 255,
        'binary' => FALSE,
        'not null' => FALSE,
      ],
      'uri' => [
        'type' => 'varchar',
        'length' => 2000,
        'binary' => FALSE,
        'not null' => FALSE,
      ],
      'created' => [
        'type' => 'int',
        'not null' => FALSE,
      ],
      'completed' => [
        'type' => 'int',
        'not null' => FALSE,
      ],
      'changed' => [
        'type' => 'int',
        'not null' => FALSE,
      ],
      'in_draft' => [
        'type' => 'int',
        'size' => 'tiny',
        'not null' => FALSE,
      ],
      'current_page' => [
        'type' => 'varchar',
        'length' => 128,
        'binary' => FALSE,
        'not null' => FALSE,
      ],
      'remote_addr' => [
        'type' => 'varchar',
        'length' => 128,
        'binary' => FALSE,
        'not null' => FALSE,
      ],
      'uid' => [
        'description' => 'The ID of the target entity.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => FALSE,
      ],
      'entity_type' => [
        'type' => 'varchar_ascii',
        'length' => 32,
        'binary' => FALSE,
        'not null' => FALSE,
      ],
      'entity_id' => [
        'type' => 'varchar',
        'length' => 255,
        'binary' => FALSE,
        'not null' => FALSE,
      ],
      'locked' => [
        'type' => 'int',
        'size' => 'tiny',
        'not null' => FALSE,
      ],
      'sticky' => [
        'type' => 'int',
        'size' => 'tiny',
        'not null' => FALSE,
      ],
      'notes' => [
        'type' => 'text',
        'size' => 'big',
        'not null' => FALSE,
      ],
    ],
    'unique keys' => [
      'webform_submission_field__uuid__value' => ['uuid'],
    ],
    'primary key' => ['sid'],
    'indexes' => [
      'webform_submission_field__webform_id__target_id' => ['webform_id'],
      'webform_submission_field__uid__target_id' => ['uid'],
      'webform_submission_field__token' => ['token'],
    ],
    'foreign keys' => [],
  ];
  Database::getConnection()
    ->schema()
    ->addIndex('webform_submission', 'webform_submission__token', ['token'], $spec);
}

/**
+ * Unsafe HMAC construction.
+ */
function webform_update_8192() {
  $invalid_signatures = [];
  $files = file_scan_directory('public://webform/','/signature-/');
  foreach ($files as $file) {
    $value = file_get_contents($file->uri);
    $value = 'data:image/png;base64,' . base64_encode($value);
    if (WebformSignatureElement::isSignatureValid($value)) {
      continue;
    }

    // Get invalid signature's submission id.
    if (preg_match('#public://webform/[a-z0-9_]+/signature/(\d+)/#', $file->uri, $match)) {
      $invalid_signatures[] = $match[1];
    }

    // Delete invalid invalid signature file.
    file_unmanaged_delete($file->uri);
  }

  // Exit if all signatures are valid.
  if (!$invalid_signatures) {
    return NULL;
  }

  // Load invalid signature's submissions.
  $webform_submissions = WebformSubmission::loadMultiple($invalid_signatures);
  if (!$webform_submissions) {
    return NULL;
  }

  // Return plain text or HTML notice.
  if (PHP_SAPI === 'cli') {
    $text = t('Invalid signature file detected and deleted. (@see @url)', ['@url' => 'https://www.drupal.org/security/psa']) . PHP_EOL;
    foreach ($webform_submissions as $webform_submission) {
      $text .= '- ' . $webform_submission->label() . ' (' .  $webform_submission->toUrl()->setAbsolute()->toString() . ')' . PHP_EOL;
    }
    return $text;
  }
  else {
    $links = [];
    foreach ($webform_submissions as $webform_submission) {
      $links[] = $webform_submission->toLink()->toRenderable();
    }

    $t_args = [':href' => 'https://www.drupal.org/security/psa'];
    $build = [
      'title' => [
        '#markup' => t('Invalid signature file detected and deleted. (@see <a href=":href">PSA-XXXXX</a>)', $t_args),
      ],
      'links' => [
        '#theme' => 'item_list',
        '#items' => $links,
      ],
    ];
    return \Drupal::service('renderer')->renderPlain($build);
  }
}
