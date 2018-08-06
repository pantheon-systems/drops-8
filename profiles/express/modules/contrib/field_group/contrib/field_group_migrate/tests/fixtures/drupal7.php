<?php
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('field_group', array(
  'fields' => array(
    'id' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ),
    'identifier' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'group_name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'entity_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'bundle' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'mode' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'parent_name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'data' => array(
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
    ),
  ),
  'primary key' => array(
    'id',
  ),
  'unique keys' => array(
    'identifier' => array(
      'identifier',
    ),
  ),
  'indexes' => array(
    'group_name' => array(
      'group_name',
    ),
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('field_group')
->fields(array(
  'id',
  'identifier',
  'group_name',
  'entity_type',
  'bundle',
  'mode',
  'parent_name',
  'data',
))
->values(array(
  'id' => '1',
  'identifier' => 'group_page|node|page|default',
  'group_name' => 'group_page',
  'entity_type' => 'node',
  'bundle' => 'page',
  'mode' => 'default',
  'parent_name' => '',
  'data' => 'a:5:{s:5:"label";s:10:"Node group";s:6:"weight";i:0;s:8:"children";a:0:{}s:11:"format_type";s:5:"htabs";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
))
->values(array(
  'id' => '2',
  'identifier' => 'group_user|user|user|default',
  'group_name' => 'group_user',
  'entity_type' => 'user',
  'bundle' => 'user',
  'mode' => 'default',
  'parent_name' => '',
  'data' => 'a:5:{s:5:"label";s:17:"User group parent";s:6:"weight";i:1;s:8:"children";a:0:{}s:11:"format_type";s:3:"div";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
))
->values(array(
  'id' => '3',
  'identifier' => 'group_user_child|user|user|default',
  'group_name' => 'group_user_child',
  'entity_type' => 'user',
  'bundle' => 'user',
  'mode' => 'default',
  'parent_name' => 'group_user',
  'data' => 'a:5:{s:5:"label";s:16:"User group child";s:6:"weight";i:99;s:8:"children";a:1:{i:0;s:12:"user_picture";}s:11:"format_type";s:4:"tabs";s:15:"format_settings";a:2:{s:5:"label";s:16:"User group child";s:17:"instance_settings";a:2:{s:7:"classes";s:16:"user-group-child";s:2:"id";s:33:"group_article_node_article_teaser";}}}',
))
->values(array(
  'id' => '4',
  'identifier' => 'group_article|node|article|teaser',
  'group_name' => 'group_article',
  'entity_type' => 'node',
  'bundle' => 'article',
  'mode' => 'teaser',
  'parent_name' => '',
  'data' => 'a:5:{s:5:"label";s:10:"htab group";s:6:"weight";i:2;s:8:"children";a:1:{i:0;s:11:"field_image";}s:11:"format_type";s:4:"htab";s:15:"format_settings";a:1:{s:17:"instance_settings";a:1:{s:7:"classes";s:10:"htab-group";}}}',
))
->values(array(
  'id' => '5',
  'identifier' => 'group_page|node|page|form',
  'group_name' => 'group_page',
  'entity_type' => 'node',
  'bundle' => 'page',
  'mode' => 'form',
  'parent_name' => '',
  'data' => 'a:5:{s:5:"label";s:15:"Node form group";s:6:"weight";i:0;s:8:"children";a:0:{}s:11:"format_type";s:5:"htabs";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
))
->values(array(
  'id' => '6',
  'identifier' => 'group_article|node|article|form',
  'group_name' => 'group_article',
  'entity_type' => 'node',
  'bundle' => 'article',
  'mode' => 'form',
  'parent_name' => '',
  'data' => 'a:5:{s:5:"label";s:15:"htab form group";s:6:"weight";i:2;s:8:"children";a:1:{i:0;s:11:"field_image";}s:11:"format_type";s:4:"htab";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
))
->execute();
