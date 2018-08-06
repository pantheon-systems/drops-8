<?php

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'entity_embed')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'entity_browser',
    'value' => 's:4:"8000";',
  ])
  ->execute();

$config = [
  'uuid' => '301adade-1c60-4b90-82dd-aa588307bc62',
  'langcode' => 'und',
  'status' => TRUE,
  'name' => 'test_update',
  'label' => 'Test update hook',
  'display' => 'iframe',
  'display_configuration' => [],
  'selection_display' => 'no_display',
  'selection_display_configuration' => [],
  'widget_selector' => 'tabs',
  'widget_selector_configuration' => [],
  'widgets' => [
    'a4ad947c-9669-497c-9988-24351955a02f' => [
      'uuid' => 'a4ad947c-9669-497c-9988-24351955a02f',
      'settings' => [
        'upload_location' => 'public://',
      ],
      'weight' => 0,
      'label' => 'Upload files',
      'id' => 'upload',
    ],
  ],
  'submit_text' => 'All animals are created equal',
];
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'entity_browser.browser.test_update',
    'data' => serialize($config),
  ])
  ->execute();

$config = Yaml::decode(file_get_contents(__DIR__ . '/../../modules/entity_browser_test/config/install/views.view.test_deprecated_field.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'views.view.test_deprecated_field',
    'data' => serialize($config),
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['entity_browser'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
