<?php

namespace Drupal\Tests\metatag\Kernel\Migrate\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Metatag-D7 field source plugin.
 *
 * @group metatag
 * @covers \Drupal\metatag\Plugin\migrate\source\d7\MetatagField
 */
class MetatagFieldTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['token', 'metatag', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['metatag'] = [
      [
        'entity_type' => 'node',
      ],
      [
        'entity_type' => 'taxonomy_term',
      ],
      [
        'entity_type' => 'user',
      ],
    ];

    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'node',
      ],
      [
        'entity_type' => 'taxonomy_term',
      ],
      [
        'entity_type' => 'user',
      ],
    ];

    return $tests;
  }

}
