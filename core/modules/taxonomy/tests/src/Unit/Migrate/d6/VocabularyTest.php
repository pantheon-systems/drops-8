<?php

/**
 * @file
 * Contains \Drupal\Tests\taxonomy\Unit\Migrate\d6\VocabularyTest.
 */

namespace Drupal\Tests\taxonomy\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 vocabulary source plugin.
 *
 * @group taxonomy
 */
class VocabularyTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\taxonomy\Plugin\migrate\source\d6\Vocabulary';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = [
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => [],
    'source' => [
      'plugin' => 'd6_vocabulary',
    ],
  ];

  protected $expectedResults = [
    [
      'vid' => 1,
      'name' => 'Tags',
      'description' => 'Tags description.',
      'help' => 1,
      'relations' => 0,
      'hierarchy' => 0,
      'multiple' => 0,
      'required' => 0,
      'tags' => 1,
      'module' => 'taxonomy',
      'weight' => 0,
      'node_types' => ['page', 'article'],
    ],
    [
      'vid' => 2,
      'name' => 'Categories',
      'description' => 'Categories description.',
      'help' => 1,
      'relations' => 1,
      'hierarchy' => 1,
      'multiple' => 0,
      'required' => 1,
      'tags' => 0,
      'module' => 'taxonomy',
      'weight' => 0,
      'node_types' => ['article'],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as &$row) {
      foreach ($row['node_types'] as $type) {
        $this->databaseContents['vocabulary_node_types'][] = [
          'type' => $type,
          'vid' => $row['vid'],
        ];
      }
      unset($row['node_types']);
    }
    $this->databaseContents['vocabulary'] = $this->expectedResults;
    parent::setUp();
  }

}
