<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\Matchers\NodeMatcherTest.
 */

namespace Drupal\linkit\Tests\Matchers;

use Drupal\linkit\Tests\LinkitTestBase;

/**
 * Tests node matcher.
 *
 * @group linkit
 */
class NodeMatcherTest extends LinkitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $type1 = $this->drupalCreateContentType(['type' => 'test1', 'name' => 'Test1']);
    $type2 = $this->drupalCreateContentType(['type' => 'test2', 'name' => 'Test2']);

    // Nodes with type 1.
    $this->drupalCreateNode(['title' => 'Lorem Ipsum 1', 'type' => $type1->id()]);
    $this->drupalCreateNode(['title' => 'Lorem Ipsum 2', 'type' => $type1->id()]);

    // Nodes with type 1.
    $this->drupalCreateNode(['title' => 'Lorem Ipsum 3', 'type' => $type2->id()]);

    // Unpublished node.
    $this->drupalCreateNode(['title' => 'Lorem unpublishd', 'type' => $type1->id(), 'status' => FALSE]);
  }

  /**
   * Tests node matcher.
   */
  function testNodeMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', []);
    $matches = $plugin->getMatches('Lorem');
    $this->assertEqual(3, count($matches), 'Correct number of matches');
  }

  /**
   * Tests node matcher with bundle filer.
   */
  function testNodeMatcherWidthBundleFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', [
      'settings' => [
        'bundles' => [
          'test1' => 'test1'
        ],
      ],
    ]);

    $matches = $plugin->getMatches('Lorem');
    $this->assertEqual(2, count($matches), 'Correct number of matches');
  }

  /**
   * Tests node matcher with include unpublished setting activated.
   */
  function testNodeMatcherWidthIncludeUnpublished() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', [
      'settings' => [
        'include_unpublished' => TRUE,
      ],
    ]);

    // Test without permissions to see unpublished nodes.
    $matches = $plugin->getMatches('Lorem');
    $this->assertEqual(3, count($matches), 'Correct number of matches');

    $account = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($account);

    // Test with permissions to see unpublished nodes.
    $matches = $plugin->getMatches('Lorem');
    $this->assertEqual(4, count($matches), 'Correct number of matches');
  }

}
