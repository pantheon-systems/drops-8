<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('metatag', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'data' => [
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
    ],
  ],
  'primary key' => [
    'entity_type',
    'entity_id',
    'revision_id',
    'language',
  ],
  'indexes' => [
    'type_revision' => [
      'entity_type',
      'revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('metatag')
  ->fields([
    'entity_type',
    'entity_id',
    'revision_id',
    'language',
    'data',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '998',
    'revision_id' => '998',
    'language' => 'und',
    'data' => serialize([
      // A very basic meta tag.
      'keywords' => ['value' => 'old revision'],
      // A meta tag that changed its tag name in D8.
      'canonical' => ['value' => 'the-node'],
      // A meta tag with multiple values.
      'robots' => [
        'value' => [
          'noindex' => 'noindex',
          'nofollow' => 'nofollow',
          'index' => 0,
          'follow' => 0,
          'noarchive' => 0,
          'nosnippet' => 0,
          'noodp' => 0,
          'noydir' => 0,
          'noimageindex' => 0,
          'notranslate' => 0,
        ],
      ],
    ]),
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '998',
    'revision_id' => '999',
    'language' => 'und',
    'data' => serialize([
      'keywords' => ['value' => 'current revision'],
      'canonical' => ['value' => 'the-node'],
      'robots' => [
        'value' => [
          'noindex' => 'noindex',
          'nofollow' => 'nofollow',
          'index' => 0,
          'follow' => 0,
          'noarchive' => 0,
          'nosnippet' => 0,
          'noodp' => 0,
          'noydir' => 0,
          'noimageindex' => 0,
          'notranslate' => 0,
        ],
      ],
    ]),
  ])
  ->values([
    'entity_type' => 'user',
    'entity_id' => '2',
    'revision_id' => '0',
    'language' => 'und',
    'data' => serialize([
      'keywords' => ['value' => 'a user'],
      'canonical' => ['value' => 'the-user'],
    ]),
  ])
  ->values([
    'entity_type' => 'taxonomy_term',
    'entity_id' => '152',
    'revision_id' => '0',
    'language' => 'und',
    'data' => serialize([
      'keywords' => ['value' => 'a taxonomy'],
      'canonical' => ['value' => 'the-term'],
    ]),
  ])
  ->execute();

$connection->insert('node')
  ->fields([
    'nid',
    'vid',
    'type',
    'language',
    'title',
    'uid',
    'status',
    'created',
    'changed',
    'comment',
    'promote',
    'sticky',
    'tnid',
    'translate',
  ])
  ->values([
    'nid' => '998',
    'vid' => '999',
    'type' => 'test_content_type',
    'language' => 'en',
    'title' => 'An Edited Node',
    'uid' => '2',
    'status' => '1',
    'created' => '1421727515',
    'changed' => '1441032132',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'tnid' => '0',
    'translate' => '0',
  ])
  ->execute();

$connection->insert('node_revision')
  ->fields([
    'nid',
    'vid',
    'uid',
    'title',
    'log',
    'timestamp',
    'status',
    'comment',
    'promote',
    'sticky',
  ])
  ->values([
    'nid' => '998',
    'vid' => '998',
    'uid' => '1',
    'title' => 'A Node',
    'log' => '',
    'timestamp' => '1441032131',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
  ])
  ->values([
    'nid' => '998',
    'vid' => '999',
    'uid' => '1',
    'title' => 'An Edited Node',
    'log' => '',
    'timestamp' => '1441032132',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
  ])
  ->execute();

$connection->insert('taxonomy_term_data')
  ->fields([
    'tid',
    'vid',
    'name',
    'description',
    'format',
    'weight',
  ])
  ->values([
    '152',
    '1',
    'A Term',
    '',
    'plain_text',
    '0',
  ])
  ->execute();

$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'sites/all/modules/metatag/metatag.module',
    'name' => 'metatag',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7115',
    'weight' => '0',
    'info' => 'a:12:{s:4:"name";s:7:"Metatag";s:11:"description";s:47:"Adds support and an API to implement meta tags.";s:7:"package";s:3:"SEO";s:4:"core";s:3:"7.x";s:12:"dependencies";a:3:{i:0;s:23:"drupal:system (>= 7.40)";i:1;s:13:"ctools:ctools";i:2;s:11:"token:token";}s:9:"configure";s:28:"admin/config/search/metatags";s:5:"files";a:30:{i:0;s:11:"metatag.inc";i:1;s:19:"metatag.migrate.inc";i:2;s:22:"metatag.search_api.inc";i:3;s:25:"tests/metatag.helper.test";i:4;s:23:"tests/metatag.unit.test";i:5;s:30:"tests/metatag.tags_helper.test";i:6;s:23:"tests/metatag.tags.test";i:7;s:23:"tests/metatag.node.test";i:8;s:23:"tests/metatag.term.test";i:9;s:23:"tests/metatag.user.test";i:10;s:35:"tests/metatag.core_tag_removal.test";i:11;s:30:"tests/metatag.bulk_revert.test";i:12;s:34:"tests/metatag.string_handling.test";i:13;s:44:"tests/metatag.string_handling_with_i18n.test";i:14;s:22:"tests/metatag.xss.test";i:15;s:33:"tests/metatag.output_caching.test";i:16;s:24:"tests/metatag.image.test";i:17;s:25:"tests/metatag.locale.test";i:18;s:33:"tests/metatag.node.with_i18n.test";i:19;s:33:"tests/metatag.term.with_i18n.test";i:20;s:35:"tests/metatag.with_i18n_output.test";i:21;s:37:"tests/metatag.with_i18n_disabled.test";i:22;s:35:"tests/metatag.with_i18n_config.test";i:23;s:26:"tests/metatag.with_me.test";i:24;s:29:"tests/metatag.with_media.test";i:25;s:30:"tests/metatag.with_panels.test";i:26;s:32:"tests/metatag.with_profile2.test";i:27;s:34:"tests/metatag.with_search_api.test";i:28;s:44:"tests/metatag.with_workbench_moderation.test";i:29;s:29:"tests/metatag.with_views.test";}s:17:"test_dependencies";a:14:{i:0;s:11:"devel:devel";i:1;s:33:"imagecache_token:imagecache_token";i:2;s:37:"entity_translation:entity_translation";i:3;s:9:"i18n:i18n";i:4;s:5:"me:me";i:5;s:23:"file_entity:file_entity";i:6;s:27:"media:media (>= 2.0, < 3.0)";i:7;s:13:"panels:panels";i:8;s:17:"profile2:profile2";i:9;s:13:"entity:entity";i:10;s:21:"search_api:search_api";i:11;s:41:"workbench_moderation:workbench_moderation";i:12;s:11:"views:views";i:13;s:15:"context:context";}s:5:"mtime";i:1550007449;s:7:"version";N;s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
  ])
  ->execute();
