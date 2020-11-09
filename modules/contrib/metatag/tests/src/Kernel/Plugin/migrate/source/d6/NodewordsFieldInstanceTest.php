<?php

namespace Drupal\Tests\metatag\Kernel\Plugin\migrate\source\d6;

use Drupal\Node\Entity\NodeType;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests Metatag-D6 field instance source plugin.
 *
 * Make sure that the migration system converts Nodewords' "type" value into a
 * string that Metatag can work with.
 *
 * @covers \Drupal\metatag\Plugin\migrate\source\d6\NodewordsFieldInstance
 *
 * @group metatag
 */
class NodewordsFieldInstanceTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Core modules.
    'field',
    'migrate_drupal',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig(static::$modules);

    // Create node types.
    $node_types = [
      'first_content_type' => 'first_content_type',
      'second_content_type' => 'second_content_type',
    ];
    foreach ($node_types as $node_type) {
      NodeType::create([
        'type' => $node_type,
        'name' => $node_type,
      ])->save();
    }

    // Setup vocabulary.
    Vocabulary::create([
      'vid' => 'test_vocabulary',
      'name' => 'test_vocabulary',
    ])->save();

    // Create a term.
    Term::create([
      'vid' => 'test_vocabulary',
      'name' => 'term',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests[0]['source_data']['nodewords'] = [
      [
        'type' => 5,
      ],
      [
        'type' => 6,
      ],
      [
        'type' => 8,
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'node',
        'type' => 5,
        'bundle' => 'first_content_type',
      ],
      [
        'entity_type' => 'node',
        'type' => 5,
        'bundle' => 'second_content_type',
      ],
      [
        'entity_type' => 'taxonomy_term',
        'type' => 6,
        'bundle' => 'test_vocabulary',
      ],
      [
        'entity_type' => 'user',
        'type' => 8,
        'bundle' => 'user',
      ],
    ];

    return $tests;
  }

}
