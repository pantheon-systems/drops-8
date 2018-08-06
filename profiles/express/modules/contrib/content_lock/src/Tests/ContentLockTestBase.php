<?php

namespace Drupal\content_lock\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;

/**
 * General setup and helper function for testing content_lock module.
 *
 * @group content_lock
 */
class ContentLockTestBase extends WebTestBase {

  use TaxonomyTestTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'language',
    'user',
    'node',
    'field',
    'field_ui',
    'taxonomy',
    'block',
    'block_content',
    'content_lock',
    'content_lock_timeout',
  ];

  /**
   * Array standard permissions for normal user.
   *
   * @var array
   */
  protected $permissions1;

  /**
   * Array standard permissions for user2.
   *
   * @var array
   */
  protected $permissions2;

  /**
   * User with permission to administer entites.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Standard User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * Standard User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article1;

  /**
   * A vocabulary created.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * A term created.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * A Block created.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $block1;

  /**
   * Setup and Rebuild node access.
   */
  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);

    $this->adminUser = $this->drupalCreateUser([
      'edit any article content',
      'delete any article content',
      'administer nodes',
      'administer content types',
      'administer users',
      'administer blocks',
      'administer taxonomy',
      'administer content lock',
    ]);

    $this->permissions1 = [
      'create article content',
      'edit any article content',
      'delete any article content',
      'access content',
      'administer blocks',
      'administer taxonomy',
    ];

    $this->permissions2 = [
      'create article content',
      'edit any article content',
      'delete any article content',
      'access content',
      'administer blocks',
      'administer taxonomy',
      'break content lock',
    ];

    // Create articles nodes.
    $this->article1 = $this->drupalCreateNode(['type' => 'article', 'title' => 'Article 1']);

    // Create block.
    $this->createBlockContentType('basic', TRUE);
    $this->block1 = $this->createBlockContent('Block 1');

    // Create vocabulary and terms.
    $this->vocabulary = $this->createVocabulary();
    $this->term1 = $this->createTerm($this->vocabulary);

    $this->user1 = $this->drupalCreateUser($this->permissions1);
    $this->user2 = $this->drupalCreateUser($this->permissions2);

    node_access_rebuild();
    $this->cronRun();

  }

  /**
   * Creates a custom block.
   *
   * @param bool|string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   * @param bool $save
   *   (optional) Whether to save the block. Defaults to TRUE.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent($title = FALSE, $bundle = 'basic', $save = TRUE) {
    $title = $title ?: $this->randomMachineName();
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
    ]);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param string $label
   *   The block type label.
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
   */
  protected function createBlockContentType($label, $create_body = FALSE) {
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ]);
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

  /**
   * Test simultaneous edit on content type article.
   */
  protected function testContentLockNode() {

    // We protect the bundle created.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'node[article]' => 1,
    ];
    $this->drupalPostForm('admin/config/content/contentlocksettings', $edit, t('Save configuration'));

    // We lock article1.
    $this->drupalLogin($this->user1);
    // Edit a node without saving.
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertText(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit article1.
    $this->drupalLogin($this->user2);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertText(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user1->getDisplayName(),
    ]));
    $this->assertLink(t('Break lock'));
    $disabled_button = $this->xpath('//input[@id=:id and @disabled="disabled"]', [':id' => 'edit-submit']);
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));
    $disabled_field = $this->xpath('//textarea[@id=:id and @disabled="disabled"]', [':id' => 'edit-body-0-value']);
    $this->assertTrue($disabled_field, t('The form cannot be submitted.'));

    // We save article 1 and unlock it.
    $this->drupalLogin($this->user1);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertText(t('This content is now locked by you against simultaneous editing.'));
    $this->drupalPostForm('/node/' . $this->article1->id() . '/edit', [], t('Save'));

    // We lock article1 with user2.
    $this->drupalLogin($this->user2);
    // Edit a node without saving.
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertText(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit article1.
    $this->drupalLogin($this->user1);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertText(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user2->getDisplayName(),
    ]));
    $this->assertNoLink(t('Break lock'));
    $disabled_button = $this->xpath('//input[@id=:id and @disabled="disabled"]', [':id' => 'edit-submit']);
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));

    // We unlock article1 with user2.
    $this->drupalLogin($this->user2);
    // Edit a node without saving.
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertText(t('This content is now locked by you against simultaneous editing.'));
    $this->drupalPostForm('/node/' . $this->article1->id() . '/edit', [], t('Save'));
    $this->assertText(t('updated.'));

  }

  /**
   * Test simultaneous edit on block.
   */
  protected function testContentLockBlock() {

    // We protect the bundle created.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'block_content[basic]' => 1,
    ];
    $this->drupalPostForm('admin/config/content/contentlocksettings', $edit, t('Save configuration'));

    // We lock block1.
    $this->drupalLogin($this->user1);
    // Edit a node without saving.
    $this->drupalGet("block/{$this->block1->id()}");
    $this->assertText(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit block1.
    $this->drupalLogin($this->user2);
    $this->drupalGet("block/{$this->block1->id()}");
    $this->assertText(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user1->getDisplayName(),
    ]));
    $this->assertLink(t('Break lock'));
    $disabled_button = $this->xpath('//input[@id=:id and @disabled="disabled"]', [':id' => 'edit-submit']);
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));

    // We save block1 and unlock it.
    $this->drupalLogin($this->user1);
    $this->drupalGet("block/{$this->block1->id()}");
    $this->assertText(t('This content is now locked by you against simultaneous editing.'));
    $this->drupalPostForm('/block/' . $this->block1->id(), [], t('Save'));

    // We lock block1 with user2.
    $this->drupalLogin($this->user2);
    // Edit a node without saving.
    $this->drupalGet("block/{$this->block1->id()}");
    $this->assertText(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit block1.
    $this->drupalLogin($this->user1);
    $this->drupalGet("block/{$this->block1->id()}");
    $this->assertText(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user2->getDisplayName(),
    ]));
    $this->assertNoLink(t('Break lock'));
    $disabled_button = $this->xpath('//input[@id=:id and @disabled="disabled"]', [':id' => 'edit-submit']);
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));

    // We unlock block1 with user2.
    $this->drupalLogin($this->user2);
    // Edit a node without saving.
    $this->drupalGet("block/{$this->block1->id()}");
    $this->assertText(t('This content is now locked by you against simultaneous editing.'));
    $this->drupalPostForm('/block/' . $this->block1->id(), [], t('Save'));
    $this->assertText(t('has been updated.'));
  }

  /**
   * Test simultaneous edit on block.
   */
  protected function testContentLockTerm() {

    // We protect the bundle created.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'taxonomy_term[' . $this->term1->bundle() . ']' => 1,
    ];
    $this->drupalPostForm('admin/config/content/contentlocksettings', $edit, t('Save configuration'));

    // We lock term1.
    $this->drupalLogin($this->user1);
    // Edit a term without saving.
    $this->drupalGet("taxonomy/term/{$this->term1->id()}/edit");
    $this->assertText(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit term1.
    $this->drupalLogin($this->user2);
    $this->drupalGet("taxonomy/term/{$this->term1->id()}/edit");
    $this->assertText(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user1->getDisplayName(),
    ]));
    $this->assertLink(t('Break lock'));
    $disabled_button = $this->xpath('//input[@id=:id and @disabled="disabled"]', [':id' => 'edit-submit']);
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));

    // We save term1 and unlock it.
    $this->drupalLogin($this->user1);
    $this->drupalGet("taxonomy/term/{$this->term1->id()}/edit");
    $this->assertText(t('This content is now locked by you against simultaneous editing.'));
    $this->drupalPostForm('/taxonomy/term/' . $this->term1->id() . '/edit', [], t('Save'));

    // We lock term1 with user2.
    $this->drupalLogin($this->user2);
    // Edit a node without saving.
    $this->drupalGet("taxonomy/term/{$this->term1->id()}/edit");
    $this->assertText(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit term1.
    $this->drupalLogin($this->user1);
    $this->drupalGet("taxonomy/term/{$this->term1->id()}/edit");
    $this->assertText(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user2->getDisplayName(),
    ]));
    $this->assertNoLink(t('Break lock'));
    $disabled_button = $this->xpath('//input[@id=:id and @disabled="disabled"]', [':id' => 'edit-submit']);
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));

    // We unlock term1 with user2.
    $this->drupalLogin($this->user2);
    // Edit a node without saving.
    $this->drupalGet("taxonomy/term/{$this->term1->id()}/edit");
    $this->assertText(t('This content is now locked by you against simultaneous editing.'));
    $this->drupalPostForm('/taxonomy/term/' . $this->term1->id() . '/edit', [], t('Save'));

  }

}
