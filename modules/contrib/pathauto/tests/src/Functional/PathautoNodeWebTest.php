<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests pathauto node UI integration.
 *
 * @group pathauto
 */
class PathautoNodeWebTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'pathauto', 'views', 'taxonomy', 'pathauto_views_test'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article']);

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'bypass node access',
      'access content overview',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->createPattern('node', '/content/[node:title]');
  }

  /**
   * Tests editing nodes with different settings.
   */
  function testNodeEditing() {
    // Ensure that the Pathauto checkbox is checked by default on the node add
    // form.
    $this->drupalGet('node/add/page');
    $this->assertFieldChecked('edit-path-0-pathauto');

    // Create a node by saving the node form.
    $title = ' Testing: node title [';
    $automatic_alias = '/content/testing-node-title';
    $this->drupalPostForm(NULL, ['title[0][value]' => $title], t('Save'));
    $node = $this->drupalGetNodeByTitle($title);

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertFieldChecked('edit-path-0-pathauto');
    $this->assertFieldByName('path[0][alias]', $automatic_alias, 'Generated alias visible in the path alias field.');

    // Check whether the alias actually works.
    $this->drupalGet($automatic_alias);
    $this->assertText($title, 'Node accessible through automatic alias.');

    // Manually set the node's alias.
    $manual_alias = '/content/' . $node->id();
    $edit = [
      'path[0][pathauto]' => FALSE,
      'path[0][alias]' => $manual_alias,
    ];
    $this->drupalPostForm($node->toUrl('edit-form'), $edit, t('Save'));
    $this->assertText(t('@type @title has been updated.', ['@type' => 'page', '@title' => $title]));

    // Check that the automatic alias checkbox is now unchecked by default.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertNoFieldChecked('edit-path-0-pathauto');
    $this->assertFieldByName('path[0][alias]', $manual_alias);

    // Submit the node form with the default values.
    $this->drupalPostForm(NULL, ['path[0][pathauto]' => FALSE], t('Save'));
    $this->assertText(t('@type @title has been updated.', ['@type' => 'page', '@title' => $title]));

    // Test that the old (automatic) alias has been deleted and only accessible
    // through the new (manual) alias.
    $this->drupalGet($automatic_alias);
    $this->assertResponse(404, 'Node not accessible through automatic alias.');
    $this->drupalGet($manual_alias);
    $this->assertText($title, 'Node accessible through manual alias.');

    // Test that the manual alias is not kept for new nodes when the pathauto
    // checkbox is ticked.
    $title = 'Automatic Title';
    $edit = [
      'title[0][value]' => $title,
      'path[0][pathauto]' => TRUE,
      'path[0][alias]' => '/should-not-get-created',
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertNoAliasExists(['alias' => 'should-not-get-created']);
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertEntityAlias($node, '/content/automatic-title');

    // Remove the pattern for nodes, the pathauto checkbox should not be
    // displayed.
    $ids = \Drupal::entityQuery('pathauto_pattern')
      ->condition('type', 'canonical_entities:node')
      ->execute();
    foreach (PathautoPattern::loadMultiple($ids) as $pattern) {
      $pattern->delete();
    }

    $this->drupalGet('node/add/article');
    $this->assertNoFieldById('edit-path-0-pathauto');
    $this->assertFieldByName('path[0][alias]', '');

    $edit = [];
    $edit['title'] = 'My test article';
    $this->drupalCreateNode($edit);
    //$this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title']);

    // Pathauto checkbox should still not exist.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertNoFieldById('edit-path-0-pathauto');
    $this->assertFieldByName('path[0][alias]', '');
    $this->assertNoEntityAlias($node);
  }

  /**
   * Test node operations.
   */
  function testNodeOperations() {
    $node1 = $this->drupalCreateNode(['title' => 'node1']);
    $node2 = $this->drupalCreateNode(['title' => 'node2']);

    // Delete all current URL aliases.
    $this->deleteAllAliases();

    $this->drupalGet('admin/content');

    // Check which of the two nodes is first.
    if (strpos($this->getTextContent(), 'node1') < strpos($this->getTextContent(), 'node2')) {
      $index = 0;
    }
    else {
      $index = 1;
    }

    $edit = [
      'action' => 'pathauto_update_alias_node',
      'node_bulk_form[' . $index . ']' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText('Update URL alias was applied to 1 item.');

    $this->assertEntityAlias($node1, '/content/' . $node1->getTitle());
    $this->assertEntityAlias($node2, '/node/' . $node2->id());
  }

  /**
   * @todo Merge this with existing node test methods?
   */
  public function testNodeState() {
    $nodeNoAliasUser = $this->drupalCreateUser(['bypass node access']);
    $nodeAliasUser = $this->drupalCreateUser(['bypass node access', 'create url aliases']);

    $node = $this->drupalCreateNode([
      'title' => 'Node version one',
      'type' => 'page',
      'path' => [
        'pathauto' => PathautoState::SKIP,
    ],
    ]);

    $this->assertNoEntityAlias($node);

    // Set a manual path alias for the node.
    $node->path->alias = '/test-alias';
    $node->save();

    // Ensure that the pathauto field was saved to the database.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $node = Node::load($node->id());
    $this->assertIdentical($node->path->pathauto, PathautoState::SKIP);

    // Ensure that the manual path alias was saved and an automatic alias was not generated.
    $this->assertEntityAlias($node, '/test-alias');
    $this->assertNoEntityAliasExists($node, '/content/node-version-one');

    // Save the node as a user who does not have access to path fieldset.
    $this->drupalLogin($nodeNoAliasUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoFieldByName('path[0][pathauto]');

    $edit = ['title[0][value]' => 'Node version two'];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Basic page Node version two has been updated.');

    $this->assertEntityAlias($node, '/test-alias');
    $this->assertNoEntityAliasExists($node, '/content/node-version-one');
    $this->assertNoEntityAliasExists($node, '/content/node-version-two');

    // Load the edit node page and check that the Pathauto checkbox is unchecked.
    $this->drupalLogin($nodeAliasUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoFieldChecked('edit-path-0-pathauto');

    // Edit the manual alias and save the node.
    $edit = [
      'title[0][value]' => 'Node version three',
      'path[0][alias]' => '/manually-edited-alias',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Basic page Node version three has been updated.');

    $this->assertEntityAlias($node, '/manually-edited-alias');
    $this->assertNoEntityAliasExists($node, '/test-alias');
    $this->assertNoEntityAliasExists($node, '/content/node-version-one');
    $this->assertNoEntityAliasExists($node, '/content/node-version-two');
    $this->assertNoEntityAliasExists($node, '/content/node-version-three');

    // Programatically save the node with an automatic alias.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $node = Node::load($node->id());
    $node->path->pathauto = PathautoState::CREATE;
    $node->save();

    // Ensure that the pathauto field was saved to the database.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $node = Node::load($node->id());
    $this->assertIdentical($node->path->pathauto, PathautoState::CREATE);

    $this->assertEntityAlias($node, '/content/node-version-three');
    $this->assertNoEntityAliasExists($node, '/manually-edited-alias');
    $this->assertNoEntityAliasExists($node, '/test-alias');
    $this->assertNoEntityAliasExists($node, '/content/node-version-one');
    $this->assertNoEntityAliasExists($node, '/content/node-version-two');

    $node->delete();
    $this->assertNull(\Drupal::keyValue('pathauto_state.node')->get($node->id()), 'Pathauto state was deleted');
  }


  /**
   * Tests that nodes without a Pathauto pattern can set custom aliases.
   */
  public function testCustomAliasWithoutPattern() {
    // First, delete all patterns to be sure that there will be no match.
    $entity_ids = \Drupal::entityQuery('pathauto_pattern')->execute();
    $entities = PathautoPattern::loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      $entity->delete();
    }

    // Next, create a node with a custom alias.
    $edit = [
      'title[0][value]' => 'Sample article',
      'path[0][alias]' => '/sample-article',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertText(t('article Sample article has been created.'));

    // Test the alias.
    $this->assertAliasExists(['alias' => '/sample-article']);
    $this->drupalGet('sample-article');
    $this->assertResponse(200, 'A node without a pattern can have a custom alias.');

    // Now create a node through the API.
    $node = Node::create(['type' => 'article', 'title' => 'Sample article API', 'path' => ['alias' => '/sample-article-api']]);
    $node->save();

    // Test the alias.
    $this->assertAliasExists(['alias' => '/sample-article-api']);
    $this->drupalGet('sample-article-api');
    $this->assertResponse(200);
  }

  /**
   * Tests that nodes with an automatic alias can get a custom alias.
   */
  public function testCustomAliasAfterAutomaticAlias() {
    // Create a pattern.
    $this->createPattern('node', '/content/[node:title]');

    // Create a node with an automatic alias.
    $edit = [
      'title[0][value]' => 'Sample article',
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertSession()->pageTextContains('article Sample article has been created.');

    // Ensure that the automatic alias got created.
    $this->assertAliasExists(['alias' => '/content/sample-article']);
    $this->drupalGet('/content/sample-article');
    $this->assertSession()->statusCodeEquals(200);

    // Now edit the node, set a custom alias.
    $edit = [
      'path[0][pathauto]' => 0,
      'path[0][alias]' => '/sample-pattern-for-article',
    ];
    $this->drupalPostForm('node/1/edit', $edit, 'Save');

    // Assert that the new alias exists and the old one does not.
    $this->assertAliasExists(['alias' => '/sample-pattern-for-article']);
    $this->assertNoAliasExists(['alias' => '/content/sample-article']);
    $this->drupalGet('sample-pattern-for-article');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests setting custom alias for nodes after removing pattern.
   *
   * Tests that nodes that had an automatic alias can get a custom alias after
   * the pathauto pattern on which the automatic alias was based, is removed.
   */
  public function testCustomAliasAfterRemovingPattern() {
    // Create a pattern.
    $this->createPattern('node', '/content/[node:title]');

    // Create a node with an automatic alias.
    $edit = [
      'title[0][value]' => 'Sample article',
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertSession()->pageTextContains('article Sample article has been created.');

    // Ensure that the automatic alias got created.
    $this->assertAliasExists(['alias' => '/content/sample-article']);
    $this->drupalGet('/content/sample-article');
    $this->assertSession()->statusCodeEquals(200);

    // Go to the edit the node form and confirm that the pathauto checkbox
    // exists.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->elementExists('css', '#edit-path-0-pathauto');

    // Delete all patterns to be sure that there will be no match.
    $entity_ids = \Drupal::entityQuery('pathauto_pattern')->execute();
    $entities = PathautoPattern::loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      $entity->delete();
    }

    // Reload the node edit form and confirm that the pathauto checkbox no
    // longer exists.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->elementNotExists('css', '#edit-path-0-pathauto');

    // Set a custom alias. We cannot disable the pathauto checkbox, because
    // there is none.
    $edit = [
      'path[0][alias]' => '/sample-alias-for-article',
    ];
    $this->submitForm($edit, 'Save');

    // Check that the new alias exists and the old one does not.
    $this->assertAliasExists(['alias' => '/sample-alias-for-article']);
    $this->assertNoAliasExists(['alias' => '/content/sample-article']);
    $this->drupalGet('sample-alias-for-article');
    $this->assertSession()->statusCodeEquals(200);
  }

}
