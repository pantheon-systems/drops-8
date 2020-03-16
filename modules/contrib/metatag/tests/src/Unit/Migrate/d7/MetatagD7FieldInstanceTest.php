<?php

namespace Drupal\Tests\metatag\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests Metatag-D7 field instance source plugin.
 *
 * @group metatag
 */
class MetatagD7FieldInstanceTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\metatag\Plugin\migrate\source\d7\MetatagFieldInstance';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_metatag_field_instance',
    ],
  ];

  protected $expectedResults = [
    [
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
    ],
    [
      'entity_type' => 'taxonomy_term',
      'bundle' => 'test_vocabulary',
    ],
    [
      'entity_type' => 'user',
      'bundle' => 'user',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['metatag'] = $this->expectedResults;

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $state = $this->getMock('Drupal\Core\State\StateInterface');
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_type_bundle_info = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeBundleInfo')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_bundle_info->expects($this->any())
      ->method('getBundleInfo')
      ->willReturnMap([
        ['node', ['test_content_type' => 'test_content_type']],
        ['taxonomy_term', ['test_vocabulary' => 'test_vocabulary']],
        ['user', ['user' => 'user']],
      ]);

    $migration = $this->getMigration();
    // @todo Replace this.
    // $migration->expects($this->any())
    // ->method('getHighWater')
    // ->will($this->returnValue(static::ORIGINAL_HIGH_WATER));
    // Setup the plugin.
    $plugin_class = static::PLUGIN_CLASS;
    $plugin = new $plugin_class($this->migrationConfiguration['source'], $this->migrationConfiguration['source']['plugin'], [], $migration, $state, $entity_manager, $entity_type_bundle_info);

    // Do some reflection to set the database and moduleHandler.
    $plugin_reflection = new \ReflectionClass($plugin);
    $database_property = $plugin_reflection->getProperty('database');
    $database_property->setAccessible(TRUE);
    $module_handler_property = $plugin_reflection->getProperty('moduleHandler');
    $module_handler_property->setAccessible(TRUE);

    // Set the database and the module handler onto our plugin.
    $database_property->setValue($plugin, $this->getDatabase($this->databaseContents + ['test_map' => []]));
    $module_handler_property->setValue($plugin, $module_handler);

    $plugin->setStringTranslation($this->getStringTranslationStub());
    $migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($plugin));
    $this->source = $plugin;
    $this->expectedCount = count($this->expectedResults);
  }

}
