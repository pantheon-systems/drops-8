<?php
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('redirect', array(
  'fields' => array(
    'rid' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ),
    'hash' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
    ),
    'type' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
    ),
    'source' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ),
    'source_options' => array(
      'type' => 'text',
      'not null' => TRUE,
    ),
    'redirect' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ),
    'redirect_options' => array(
      'type' => 'text',
      'not null' => TRUE,
    ),
    'language' => array(
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ),
    'status_code' => array(
      'type' => 'int',
      'size' => 'small',
      'not null' => TRUE,
    ),
    'count' => array(
      'type' => 'int',
      'not null' => TRUE,
    ),
    'access' => array(
      'type' => 'int',
      'not null' => TRUE,
    ),
  ),
  'primary key' => array('rid'),
  'unique keys' => array(
    'source_language' => array('source', 'language'),
    'expires' => array('type', 'access')
  ),
  'mysql_character_set' => 'utf8',
));


$connection->insert('redirect')
  ->fields(array(
    'rid',
    'hash',
    'type',
    'uid',
    'source',
    'source_options',
    'redirect',
    'redirect_options',
    'language',
    'status_code',
    'count',
    'access',
  ))
  ->values(array(
    'rid' => 5,
    'hash' => 'MwmDbnA65ag646gtEdLqmAqTbF0qQerse63RkQmJK_Y',
    'type' => 'redirect',
    'uid' => 5,
    'source' => 'test/source/url',
    'source_options' => '',
    'redirect' => 'test/redirect/url',
    'redirect_options' => '',
    'language' => 'und',
    'status_code' => 301,
    'count' => 2518,
    'access' => 1449497138,
  ))
  ->values(array(
    'rid' => 7,
    'hash' => 'GvD5bBB71W8qBvp9I9hHmbSoqZfTvUz0mIkEWjlP8M4',
    'type' => 'redirect',
    'uid' => 6,
    'source' => 'test/source/url2',
    'source_options' => '',
    'redirect' => 'http://test/external/redirect/url',
    'redirect_options' => 'a:1:{s:5:"query";a:2:{s:3:"foo";s:3:"bar";s:3:"biz";s:3:"buz";}}',
    'language' => 'und',
    'status_code' => 0,
    'count' => 419,
    'access' => 1449497139,
  ))
  ->execute();

$connection->schema()->createTable('variable', array(
  'fields' => array(
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'value' => array(
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'name',
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('variable')
->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'redirect_default_status_code',
  'value' => 's:3:"307";',
))
->execute();

$connection->schema()->createTable('system', array(
  'fields' => array(
    'filename' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '12',
      'default' => '',
    ),
    'owner' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'bootstrap' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'schema_version' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '-1',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'info' => array(
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'filename',
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('system')
->fields(array(
  'filename',
  'name',
  'type',
  'owner',
  'status',
  'bootstrap',
  'schema_version',
  'weight',
  'info',
))
->values(array(
  'filename' => 'modules/contrib/redirect/redirect.module',
  'name' => 'redirect',
  'type' => 'module',
  'owner' => '',
  'status' => '1',
  'bootstrap' => '0',
  'schema_version' => '7000',
  'weight' => '0',
  'info' => 'a:13:{s:4:"name";s:8:"Redirect";s:11:"description";s:51:"Allows users to redirect from old URLs to new URLs.";s:4:"core";s:3:"7.x";s:5:"files";a:11:{i:0;s:15:"redirect.module";i:1;s:18:"redirect.admin.inc";i:2;s:16:"redirect.install";i:3;s:13:"redirect.test";i:4;s:24:"views/redirect.views.inc";i:5;s:47:"views/redirect_handler_filter_redirect_type.inc";i:6;s:48:"views/redirect_handler_field_redirect_source.inc";i:7;s:50:"views/redirect_handler_field_redirect_redirect.inc";i:8;s:52:"views/redirect_handler_field_redirect_operations.inc";i:9;s:51:"views/redirect_handler_field_redirect_link_edit.inc";i:10;s:53:"views/redirect_handler_field_redirect_link_delete.inc";}s:9:"configure";s:37:"admin/config/search/redirect/settings";s:7:"version";s:11:"7.x-1.0-rc1";s:7:"project";s:8:"redirect";s:9:"datestamp";s:10:"1347989995";s:5:"mtime";i:1347989995;s:12:"dependencies";a:0:{}s:7:"package";s:5:"Other";s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
))
->execute();
