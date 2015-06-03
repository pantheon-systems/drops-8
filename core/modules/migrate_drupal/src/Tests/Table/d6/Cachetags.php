<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Cachetags.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cachetags table.
 */
class Cachetags extends DrupalDumpBase {

  public function load() {
    $this->createTable("cachetags", array(
      'primary key' => array(
        'tag',
      ),
      'fields' => array(
        'tag' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'invalidations' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'deletions' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("cachetags")->fields(array(
      'tag',
      'invalidations',
      'deletions',
    ))
    ->execute();
  }

}
#618a5d0e2f6bb7fbd27a98ec94c37cc5
