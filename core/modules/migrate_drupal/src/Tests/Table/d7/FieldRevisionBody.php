<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldRevisionBody.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_revision_body table.
 */
class FieldRevisionBody extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_revision_body", array(
      'primary key' => array(
        'entity_type',
        'deleted',
        'entity_id',
        'revision_id',
        'language',
        'delta',
      ),
      'fields' => array(
        'entity_type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'bundle' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'deleted' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'entity_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'revision_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'delta' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'body_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'body_summary' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'body_format' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("field_revision_body")->fields(array(
      'entity_type',
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'language',
      'delta',
      'body_value',
      'body_summary',
      'body_format',
    ))
    ->values(array(
      'entity_type' => 'node',
      'bundle' => 'article',
      'deleted' => '0',
      'entity_id' => '2',
      'revision_id' => '2',
      'language' => 'und',
      'delta' => '0',
      'body_value' => "...is that it's the absolute best show ever. Trust me, I would know.",
      'body_summary' => '',
      'body_format' => 'filtered_html',
    ))->execute();
  }

}
#eb2f8f6fd180db91769e5e2a6d5ff950
