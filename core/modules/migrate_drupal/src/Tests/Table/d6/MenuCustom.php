<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\MenuCustom.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the menu_custom table.
 */
class MenuCustom extends DrupalDumpBase {

  public function load() {
    $this->createTable("menu_custom", array(
      'primary key' => array(
        'menu_name',
      ),
      'fields' => array(
        'menu_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("menu_custom")->fields(array(
      'menu_name',
      'title',
      'description',
    ))
    ->values(array(
      'menu_name' => 'navigation',
      'title' => 'Navigation',
      'description' => 'The navigation menu is provided by Drupal and is the main interactive menu for any site. It is usually the only menu that contains personalized links for authenticated users, and is often not even visible to anonymous users.',
    ))->values(array(
      'menu_name' => 'primary-links',
      'title' => 'Primary links',
      'description' => 'Primary links are often used at the theme layer to show the major sections of a site. A typical representation for primary links would be tabs along the top.',
    ))->values(array(
      'menu_name' => 'secondary-links',
      'title' => 'Secondary links',
      'description' => 'Secondary links are often used for pages like legal notices, contact details, and other secondary navigation items that play a lesser role than primary links',
    ))->execute();
  }

}
#9c671dfb6963883b73f601e2e80739ed
