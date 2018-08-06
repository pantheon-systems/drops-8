<?php

namespace Drupal\webform\Tests\Block;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform block context.
 *
 * @group Webform
 */
class WebformBlockContextTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_node', 'webform_test_block_context'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Manually install blocks.
    $contexts = [
      'webform' => '@webform.webform_route_context:webform',
      'webform_submission' => '@webform.webform_submission_route_context:webform_submission',
      'node' => '@node.node_route_context:node',
    ];
    foreach ($contexts as $type => $context) {
      $block = $this->placeBlock('webform_test_block_context_block', ['label' => '{' . $type . ' context}']);
      $block->setVisibilityConfig('webform', [
        'id' => 'webform',
        'webforms' => ['contact' => 'contact'],
        'negate' => FALSE,
        'context_mapping' => [
          $type => $context,
        ],
      ]);
      $block->save();
    }
    $block = $this->placeBlock('webform_test_block_context_block', ['label' => '{all contexts}']);
    $block->setVisibilityConfig('webform', [
      'id' => 'webform',
      'webforms' => ['contact' => 'contact'],
      'negate' => FALSE,
      'context_mapping' => $contexts,
    ]);
    $block->save();
  }

  /**
   * Tests webform block context.
   */
  public function testBlockContext() {
    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('contact');

    // Check webform context.
    $this->drupalGet('webform/contact');
    $this->assertRaw('{all contexts}');
    $this->assertRaw('{webform context}');

    // Check webform submission context.
    $sid = $this->postSubmissionTest($webform);
    $this->drupalGet("/admin/structure/webform/manage/contact/submission/$sid");
    $this->assertRaw('{all contexts}');
    $this->assertRaw('{webform_submission context}');

    // Check webform node context.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $node->webform->target_id = 'contact';
    $node->webform->status = 1;
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('{all contexts}');
    $this->assertRaw('{node context}');
  }

}
