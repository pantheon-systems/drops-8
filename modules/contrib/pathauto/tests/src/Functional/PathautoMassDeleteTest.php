<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\pathauto\PathautoState;
use Drupal\Tests\BrowserTestBase;

/**
 * Mass delete functionality tests.
 *
 * @group pathauto
 */
class PathautoMassDeleteTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'taxonomy', 'pathauto'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodes;

  /**
   * The test accounts.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $accounts;

  /**
   * The test terms.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $terms;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->createPattern('node', '/content/[node:title]');
    $this->createPattern('user', '/users/[user:name]');
    $this->createPattern('taxonomy_term', '/[term:vocabulary]/[term:name]');
  }

  /**
   * Tests the deletion of all the aliases.
   */
  function testDeleteAll() {
    /** @var \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper */
    $alias_storage_helper = \Drupal::service('pathauto.alias_storage_helper');

    // 1. Test that deleting all the aliases, of any type, works.
    $this->generateAliases();
    $edit = [
      'delete[all_aliases]' => TRUE,
      'options[keep_custom_aliases]' => FALSE,
    ];
    $this->drupalPostForm('admin/config/search/path/delete_bulk', $edit, t('Delete aliases now!'));
    $this->assertText(t('All of your path aliases have been deleted.'));
    $this->assertUrl('admin/config/search/path/delete_bulk');

    // Make sure that all of them are actually deleted.
    $this->assertEquals(0, $alias_storage_helper->countAll(), 'All the aliases have been deleted.');

    // 2. Test deleting only specific (entity type) aliases.
    $manager = $this->container->get('plugin.manager.alias_type');
    $pathauto_plugins = ['canonical_entities:node' => 'nodes', 'canonical_entities:taxonomy_term' => 'terms', 'canonical_entities:user' => 'accounts'];
    foreach ($pathauto_plugins as $pathauto_plugin => $attribute) {
      $this->generateAliases();
      $edit = [
        'delete[plugins][' . $pathauto_plugin . ']' => TRUE,
        'options[keep_custom_aliases]' => FALSE,
      ];
      $this->drupalPostForm('admin/config/search/path/delete_bulk', $edit, t('Delete aliases now!'));
      $alias_type = $manager->createInstance($pathauto_plugin);
      $this->assertRaw(t('All of your %label path aliases have been deleted.', ['%label' => $alias_type->getLabel()]));
      // Check that the aliases were actually deleted.
      foreach ($this->{$attribute} as $entity) {
        $this->assertNoEntityAlias($entity);
      }

      // Check that the other aliases are not deleted.
      foreach ($pathauto_plugins as $_pathauto_plugin => $_attribute) {
        // Skip the aliases that should be deleted.
        if ($_pathauto_plugin == $pathauto_plugin) {
          continue;
        }
        foreach ($this->{$_attribute} as $entity) {
          $this->assertEntityAliasExists($entity);
        }
      }
    }

    // 3. Test deleting automatically generated aliases only.
    $this->generateAliases();
    $edit = [
      'delete[all_aliases]' => TRUE,
      'options[keep_custom_aliases]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/path/delete_bulk', $edit, t('Delete aliases now!'));
    $this->assertText(t('All of your automatically generated path aliases have been deleted.'));
    $this->assertUrl('admin/config/search/path/delete_bulk');

    // Make sure that only custom aliases and aliases with no information about
    // their state still exist.
    $this->assertEquals(3, $alias_storage_helper->countAll(), 'Custom aliases still exist.');
    $this->assertEquals('/node/101', $alias_storage_helper->loadBySource('/node/101', 'en')['source']);
    $this->assertEquals('/node/104', $alias_storage_helper->loadBySource('/node/104', 'en')['source']);
    $this->assertEquals('/node/105', $alias_storage_helper->loadBySource('/node/105', 'en')['source']);
  }

  /**
   * Helper function to generate aliases.
   */
  function generateAliases() {
    // Delete all aliases to avoid duplicated aliases. They will be recreated
    // below.
    $this->deleteAllAliases();

    // We generate a bunch of aliases for nodes, users and taxonomy terms. If
    // the entities are already created we just update them, otherwise we create
    // them.
    if (empty($this->nodes)) {
      // Create a large number of nodes (100+) to make sure that the batch code
      // works.
      for ($i = 1; $i <= 105; $i++) {
        // Set the alias of two nodes manually.
        $settings = ($i > 103) ? ['path' => ['alias' => "/custom_alias_$i", 'pathauto' => PathautoState::SKIP]] : [];
        $node = $this->drupalCreateNode($settings);
        $this->nodes[$node->id()] = $node;
      }
    }
    else {
      foreach ($this->nodes as $node) {
        if ($node->id() > 103) {
          // The alias is set manually.
          $node->set('path', ['alias' => '/custom_alias_' . $node->id()]);
        }
        $node->save();
      }
    }
    // Delete information about the state of an alias to make sure that aliases
    // with no such data are left alone by default.
    \Drupal::keyValue('pathauto_state.node')->delete(101);

    if (empty($this->accounts)) {
      for ($i = 1; $i <= 5; $i++) {
        $account = $this->drupalCreateUser();
        $this->accounts[$account->id()] = $account;
      }
    }
    else {
      foreach ($this->accounts as $id => $account) {
        $account->save();
      }
    }

    if (empty($this->terms)) {
      $vocabulary = $this->addVocabulary(['name' => 'test vocabulary', 'vid' => 'test_vocabulary']);
      for ($i = 1; $i <= 5; $i++) {
        $term = $this->addTerm($vocabulary);
        $this->terms[$term->id()] = $term;
      }
    }
    else {
      foreach ($this->terms as $term) {
        $term->save();
      }
    }

    // Check that we have aliases for the entities.
    foreach (['nodes', 'accounts', 'terms'] as $attribute) {
      foreach ($this->{$attribute} as $entity) {
        $this->assertEntityAliasExists($entity);
      }
    }
  }

}
