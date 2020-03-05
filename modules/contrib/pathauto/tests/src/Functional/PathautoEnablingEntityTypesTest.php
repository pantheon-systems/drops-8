<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\comment\Tests\CommentTestTrait;

/**
 * Tests pathauto settings form.
 *
 * @group pathauto
 */
class PathautoEnablingEntityTypesTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'pathauto', 'comment'];

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

    $this->drupalCreateContentType(['type' => 'article']);
    $this->addDefaultCommentField('node', 'article');

    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'administer nodes',
      'post comments',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * A suite of tests to verify if the feature to enable and disable the
   * ability to define alias patterns for a given entity type works. Test with
   * the comment module, as it is not enabled by default.
   */
  function testEnablingEntityTypes() {
    // Verify that the comment entity type is not available when trying to add
    // a new pattern, nor "broken".
    $this->drupalGet('/admin/config/search/path/patterns/add');
    $this->assertCount(0, $this->cssSelect('option[value = "canonical_entities:comment"]:contains(Comment)'));
    $this->assertCount(0, $this->cssSelect('option:contains(Broken)'));

    // Enable the entity type and create a pattern for it.
    $this->drupalGet('/admin/config/search/path/settings');
    $edit = [
      'enabled_entity_types[comment]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->createPattern('comment', '/comment/[comment:body]');

    // Create a node, a comment type and a comment entity.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('/node/' . $node->id());
    $edit = [
      'comment_body[0][value]' => 'test-body',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Verify that an alias has been generated and that the type can no longer
    // be disabled.
    $this->assertAliasExists(['alias' => '/comment/test-body']);
    $this->drupalGet('/admin/config/search/path/settings');
    $this->assertCount(1, $this->cssSelect('input[name = "enabled_entity_types[comment]"][disabled = "disabled"]'));
  }

}
