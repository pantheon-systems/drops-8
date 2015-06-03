<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBlockContentTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Language\Language;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade custom blocks.
 *
 * @group migrate_drupal
 */
class MigrateBlockContentTest extends MigrateDrupal6TestBase {

  static $modules = array('block', 'block_content', 'filter', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('block_content'));
    $this->installEntitySchema('block_content');

    $migration = entity_load('migration', 'd6_block_content_type');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
    $migration = entity_load('migration', 'd6_block_content_body_field');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $this->prepareMigrations(array(
      'd6_filter_format' => array(
        array(array(2), array('full_html'))
      )
    ));
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_custom_block');
    $dumps = array(
      $this->getDumpDirectory() . '/Boxes.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 custom block to Drupal 8 migration.
   */
  public function testBlockMigration() {
    /** @var BlockContent $block */
    $block = BlockContent::load(1);
    $this->assertIdentical('My block 1', $block->label());
    $this->assertTrue(REQUEST_TIME <= $block->getChangedTime() && $block->getChangedTime() <= time());
    $this->assertIdentical('en', $block->language()->getId());
    $this->assertIdentical('<h3>My first custom block body</h3>', $block->body->value);
    $this->assertIdentical('full_html', $block->body->format);

    $block = BlockContent::load(2);
    $this->assertIdentical('My block 2', $block->label());
    $this->assertTrue(REQUEST_TIME <= $block->getChangedTime() && $block->getChangedTime() <= time());
    $this->assertIdentical('en', $block->language()->getId());
    $this->assertIdentical('<h3>My second custom block body</h3>', $block->body->value);
    $this->assertIdentical('full_html', $block->body->format);
  }

}
