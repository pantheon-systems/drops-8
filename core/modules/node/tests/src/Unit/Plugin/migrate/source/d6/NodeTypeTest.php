<?php

/**
 * @file
 * Contains \Drupal\Tests\node\Unit\Plugin\migrate\source\d6\NodeTypeTest.
 */

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 node type source plugin.
 *
 * @group node
 */
class NodeTypeTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\node\Plugin\migrate\source\d6\NodeType';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test_nodetypes',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_nodetype',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  protected $expectedResults = array(
    array(
      'type' => 'page',
      'name' => 'Page',
      'module' => 'node',
      'description' => 'A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an "About us" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site\'s initial home page.',
      'help' => '',
      'title_label' => 'Title',
      'has_body' => 1,
      'body_label' => 'Body',
      'min_word_count' => 0,
      'custom' => 1,
      'modified' => 0,
      'locked' => 0,
      'orig_type' => 'page',
    ),
    array(
      'type' => 'story',
      'name' => 'Story',
      'module' => 'node',
      'description' => 'A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site\'s initial home page, and provides the ability to post comments.',
      'help' => '',
      'title_label' => 'Title',
      'has_body' => 1,
      'body_label' => 'Body',
      'min_word_count' => 0,
      'custom' => 1,
      'modified' => 0,
      'locked' => 0,
      'orig_type' => 'story',
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = $this->expectedResults;
    parent::setUp();
  }

}
