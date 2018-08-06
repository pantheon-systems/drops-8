<?php

namespace Drupal\Tests\redirect\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 redirect source plugin.
 *
 * @group redirect
 */
class PathRedirectTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\redirect\Plugin\migrate\source\d7\PathRedirect';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_path_redirect',
    ],
  ];

  protected $expectedResults = [
    [
      'rid' => 5,
      'hash' => 'MwmDbnA65ag646gtEdLqmAqTbF0qQerse63RkQmJK_Y',
      'type' => 'redirect',
      'uid' => 5,
      'source' => 'test/source/url',
      'source_options' => '',
      'redirect' => 'test/redirect/url',
      'redirect_options' => '',
      'language' => 'und',
      'status_code' => 301,
      'count' => 2518,
      'access' => 1449497138,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['variable'] = [
      [
        'name' => 'redirect_default_status_code',
        'value' => 's:3:"307";',
      ]
    ];
    $this->databaseContents['redirect'] = $this->expectedResults;
    parent::setUp();
  }

}
