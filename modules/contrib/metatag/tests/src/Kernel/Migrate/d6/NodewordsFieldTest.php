<?php

namespace Drupal\Tests\metatag\Kernel\Migrate\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Nodewords-D6 field source plugin.
 *
 * Make sure that the migration system converts Nodewords' "type" value into a
 * string that Metatag can work with.
 *
 * @see Drupal\metatag\Plugin\migrate\source\d6\NodewordsField::initializeIterator()
 *
 * @group metatag
 *
 * @covers \Drupal\metatag\Plugin\migrate\source\d6\NodewordsField
 */
class NodewordsFieldTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['metatag', 'migrate_drupal', 'token'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    return [
      [
        // Example source data for the test. This test is focused on making sure
        // that Nodewords' integer values are converted to Metatag's strings.
        [
          'nodewords' => [
            // This represents a node.
            [
              'type' => '5',
            ],
            // This represents a taxonomy term.
            [
              'type' => '6',
            ],
            // This represents a user.
            [
              'type' => '8',
            ],
          ],
        ],
        // Expected results after going through the conversion process. After
        // going through the initializeIterator() method, this is what the
        // 'nodewords' value of the database's (faked) contents above should be
        // turned into.
        [
          [
            'entity_type' => 'node',
            'type' => '5',
          ],
          [
            'entity_type' => 'taxonomy_term',
            'type' => '6',
          ],
          [
            'entity_type' => 'user',
            'type' => '8',
          ],
        ],
      ],
    ];
  }

}
