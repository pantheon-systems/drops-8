<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldDataFieldDateWithEndTime.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_data_field_date_with_end_time table.
 */
class FieldDataFieldDateWithEndTime extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_data_field_date_with_end_time", array(
      'primary key' => array(
        'entity_type',
        'deleted',
        'entity_id',
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
          'length' => '4',
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
          'not null' => FALSE,
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
        'field_date_with_end_time_value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'field_date_with_end_time_value2' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
      ),
    ));
    $this->database->insert("field_data_field_date_with_end_time")->fields(array(
      'entity_type',
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'language',
      'delta',
      'field_date_with_end_time_value',
      'field_date_with_end_time_value2',
    ))
    ->values(array(
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'deleted' => '0',
      'entity_id' => '1',
      'revision_id' => '1',
      'language' => 'und',
      'delta' => '0',
      'field_date_with_end_time_value' => '1421727300',
      'field_date_with_end_time_value2' => '1421727300',
    ))->execute();
  }

}
#4e2bea2cfa0c14c5d0220b48e8a862d2
