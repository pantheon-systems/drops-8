<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\source\d6\FieldInstancePerViewModeTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 fields per view mode source plugin.
 *
 * @group field
 */
class FieldInstancePerViewModeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\field\Plugin\migrate\source\d6\FieldInstancePerViewMode';

  protected $migrationConfiguration = array(
    'id' => 'view_mode_test',
    'source' => array(
      'plugin' => 'd6_field_instance_per_view_mode',
    ),
  );

  protected $expectedResults = array(
    array(
      'entity_type' => 'node',
      'view_mode' => 4,
      'type_name' => 'article',
      'field_name' => 'field_test',
      'type' => 'text',
      'module' => 'text',
      'weight' => 1,
      'label' => 'above',
      'display_settings' => array(
        'weight' => 1,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        4 => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
      ),
      'widget_settings' => array(),
    ),
    array(
      'entity_type' => 'node',
      'view_mode' => 'teaser',
      'type_name' => 'story',
      'field_name' => 'field_test',
      'type' => 'text',
      'module' => 'text',
      'weight' => 2,
      'label' => 'above',
      'display_settings' => array(
        'weight' => 1,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
      ),
      'widget_settings' => array(),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $field_view_mode) {
      // These are stored as serialized strings.
      $field_view_mode['display_settings'] = serialize($field_view_mode['display_settings']);
      $field_view_mode['widget_settings'] = serialize($field_view_mode['widget_settings']);

      $this->databaseContents['content_node_field'][] = array(
        'field_name' => $field_view_mode['field_name'],
        'type' => $field_view_mode['type'],
        'module' => $field_view_mode['module'],
      );
      unset($field_view_mode['type']);
      unset($field_view_mode['module']);

      $this->databaseContents['content_node_field_instance'][] = $field_view_mode;

      // Update the expected display settings.
      $this->expectedResults[$k]['display_settings'] = $this->expectedResults[$k]['display_settings'][$field_view_mode['view_mode']];

    }
    parent::setUp();
  }

}
