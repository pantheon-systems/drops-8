<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyFieldTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary field migration.
 *
 * @group taxonomy
 */
class MigrateVocabularyFieldTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('node', 'taxonomy', 'field', 'text', 'entity_reference');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_taxonomy_vocabulary' => array(
        array(array(4), array('tags')),
      ),
    );
    $this->prepareMigrations($id_mappings);

    entity_create('taxonomy_vocabulary', array(
      'name' => 'Test Vocabulary',
      'description' => 'Test Vocabulary',
      'vid' => 'test_vocab',
    ))->save();

    $this->loadDumps(['Vocabulary.php', 'VocabularyNodeTypes.php']);
    $this->executeMigration('d6_vocabulary_field');
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyField() {
    // Test that the field exists.
    $field_storage_id = 'node.tags';
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertIdentical($field_storage_id, $field_storage->id());

    $settings = $field_storage->getSettings();
    $this->assertIdentical('taxonomy_term', $settings['target_type'], "Target type is correct.");

    $this->assertIdentical(array('node', 'tags'), entity_load('migration', 'd6_vocabulary_field')->getIdMap()->lookupDestinationID(array(4)), "Test IdMap");
  }

}
