<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathauto\PathautoState;
use Drupal\Tests\BrowserTestBase;

/**
 * Bulk update functionality tests.
 *
 * @group pathauto
 */
class PathautoBulkUpdateTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'pathauto', 'forum'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The created nodes.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodes;

  /**
   * The created patterns.
   *
   * @var \Drupal\pathauto\PathautoPatternInterface
   */
  protected $patterns;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'administer forums',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->patterns = [];
    $this->patterns['node'] = $this->createPattern('node', '/content/[node:title]');
    $this->patterns['user'] = $this->createPattern('user', '/users/[user:name]');
    $this->patterns['forum'] = $this->createPattern('forum', '/forums/[term:name]');
  }

  function testBulkUpdate() {
    // Create some nodes.
    $this->nodes = [];
    for ($i = 1; $i <= 5; $i++) {
      $node = $this->drupalCreateNode();
      $this->nodes[$node->id()] = $node;
    }

    // Clear out all aliases.
    $this->deleteAllAliases();

    // Bulk create aliases.
    $edit = [
      'update[canonical_entities:node]' => TRUE,
      'update[canonical_entities:user]' => TRUE,
      'update[forum]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/path/update_bulk', $edit, t('Update'));

    // This has generated 8 aliases: 5 nodes, 2 users and 1 forum.
    $this->assertText('Generated 8 URL aliases.');

    // Check that aliases have actually been created.
    foreach ($this->nodes as $node) {
      $this->assertEntityAliasExists($node);
    }
    $this->assertEntityAliasExists($this->adminUser);
    // This is the default "General discussion" forum.
    $this->assertAliasExists(['source' => '/taxonomy/term/1']);

    // Add a new node.
    $new_node = $this->drupalCreateNode(['path' => ['alias' => '', 'pathauto' => PathautoState::SKIP]]);

    // Run the update again which should not run against any nodes.
    $this->drupalPostForm('admin/config/search/path/update_bulk', $edit, t('Update'));
    $this->assertText('No new URL aliases to generate.');
    $this->assertNoEntityAliasExists($new_node);

    // Make sure existing aliases can be overridden.
    $this->drupalPostForm('admin/config/search/path/settings', ['update_action' => PathautoGeneratorInterface::UPDATE_ACTION_DELETE], t('Save configuration'));

    // Patterns did not change, so no aliases should be regenerated.
    $edit['action'] = 'all';
    $this->drupalPostForm('admin/config/search/path/update_bulk', $edit, t('Update'));
    $this->assertText('No new URL aliases to generate.');

    // Update the node pattern, and leave other patterns alone. Existing nodes
    // should get a new alias, except the node above whose alias is manually
    // set. Other aliases must be left alone.
    $this->patterns['node']->delete();
    $this->patterns['node'] = $this->createPattern('node', '/archive/node-[node:nid]');

    $this->drupalPostForm('admin/config/search/path/update_bulk', $edit, t('Update'));
    $this->assertText('Generated 5 URL aliases.');

    // Prevent existing aliases to be overridden. The bulk generate page should
    // only offer to create an alias for paths which have none.
    $this->drupalPostForm('admin/config/search/path/settings', ['update_action' => PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW], t('Save configuration'));

    $this->drupalGet('admin/config/search/path/update_bulk');
    $this->assertFieldByName('action', 'create');
    $this->assertText('Pathauto settings are set to ignore paths which already have a URL alias.');
    $this->assertNoFieldByName('action', 'update');
    $this->assertNoFieldByName('action', 'all');
  }

  /**
   * Tests alias generation for nodes that existed before installing Pathauto.
   */
  function testBulkUpdateExistingContent() {
    // Create a node.
    $node = $this->drupalCreateNode();

    // Delete its alias and Pathauto metadata.
    \Drupal::service('pathauto.alias_storage_helper')->deleteEntityPathAll($node);
    $node->path->first()->get('pathauto')->purge();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);

    // Execute bulk generation.
    // Bulk create aliases.
    $edit = [
      'update[canonical_entities:node]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/path/update_bulk', $edit, t('Update'));

    // Verify that the alias was created for the node.
    $this->assertText('Generated 1 URL alias.');
  }

}
