<?php

namespace Drupal\Tests\token\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests block tokens.
 *
 * @group token
 */
class TokenBlockTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'views', 'block_content'];

  /**
   * {@inheritdoc}
   */
  public function setUp($modules = []) {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['access content', 'administer blocks']);
    $this->drupalLogin($this->admin_user);
  }

  public function testBlockTitleTokens() {
    $label = 'tokenblock';
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE
    ]);
    $bundle->save();

    $block_content = BlockContent::create([
      'type' => $label,
      'label' => '[current-page:title] block title',
      'info' => 'Test token title block',
      'body[value]' => 'This is the test token title block.',
    ]);
    $block_content->save();

    $block = $this->drupalPlaceBlock('block_content:' . $block_content->uuid(), [
      'label' => '[user:name]',
    ]);
    $this->drupalGet($block->toUrl());
    // Ensure that the link to available tokens is present and correctly
    // positioned.
    $this->assertLink('Browse available tokens.');
    $this->assertText('This field supports tokens. Browse available tokens.');
    $this->drupalPostForm(NULL, [], t('Save block'));
    // Ensure token validation is working on the block.
    $this->assertText('Title is using the following invalid tokens: [user:name].');

    // Create the block for real now with a valid title.
    $settings = $block->get('settings');
    $settings['label'] = '[current-page:title] block title';
    $block->set('settings', $settings);
    $block->save();

    // Ensure that tokens are not double-escaped when output as a block title.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode(['title' => "Site's first node"]);
    $this->drupalGet('node/' . $node->id());
    // The apostraphe should only be escaped once.
    $this->assertRaw("Site&#039;s first node block title");
  }
}
