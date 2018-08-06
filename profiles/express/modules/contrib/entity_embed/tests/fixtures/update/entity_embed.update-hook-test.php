<?php

/**
 * @file
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'entity_embed')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'entity_embed',
    'value' => 's:4:"8001";',
  ])
  ->execute();

$config = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/optional/embed.button.node.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'embed.button.node',
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
$extensions['module']['embed'] = 8000;
$extensions['module']['entity_embed'] = 8001;
$extensions['module']['embed'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
