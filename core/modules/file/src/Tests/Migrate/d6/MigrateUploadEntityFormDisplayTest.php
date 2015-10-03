<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\d6\MigrateUploadEntityFormDisplayTest.
 */

namespace Drupal\file\Tests\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upload form entity display.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadEntityFormDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
    $this->executeMigration('d6_upload_entity_form_display');
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 entity form display migration.
   */
  public function testUploadEntityFormDisplay() {
    $display = EntityFormDisplay::load('node.page.default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_generic', $component['type']);

    $display = EntityFormDisplay::load('node.story.default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_generic', $component['type']);

    // Assure this doesn't exist.
    $display = EntityFormDisplay::load('node.article.default');
    $component = $display->getComponent('upload');
    $this->assertTrue(is_null($component));

    $this->assertIdentical(array('node', 'page', 'default', 'upload'), Migration::load('d6_upload_entity_form_display')->getIdMap()->lookupDestinationID(array('page')));
  }

}
