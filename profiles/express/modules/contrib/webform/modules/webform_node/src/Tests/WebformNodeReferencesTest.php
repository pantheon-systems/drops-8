<?php

namespace Drupal\webform_node\Tests;

/**
 * Tests for webform node references.
 *
 * @group WebformNode
 */
class WebformNodeReferencesTest extends WebformNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'help', 'webform', 'webform_node'];

  /**
   * Tests webform node references.
   */
  public function testReferences() {
    global $base_path;

    $this->drupalLogin($this->rootUser);
    $this->drupalPlaceBlock('help_block');

    // Check references tab's empty message.
    $this->drupalGet('admin/structure/webform/manage/contact/references');
    $this->assertRaw('There are no webform node references.');

    // Create webform node.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $node->webform->target_id = 'contact';
    $node->save();

    $this->drupalGet('admin/structure/webform/manage/contact/references');

    // Check references tab does not include empty message.
    $this->assertNoRaw('There are no webform node references.');

    // Check references tabs includes webform node.
    $this->assertLink($node->label());

    // Check references tab local actions.
    $this->assertRaw('<li><a href="' . $base_path . 'node/add/webform?webform_id=contact" class="button button-action" data-drupal-link-query="{&quot;webform_id&quot;:&quot;contact&quot;}" data-drupal-link-system-path="node/add/webform">Add Webform</a></li>');

    // Check node with prepopulated webform.
    $this->drupalGet('node/add/webform', ['query' => ['webform_id' => 'contact']]);
    $this->assertFieldByName('webform[0][target_id]', 'contact');

    // Check node without prepopulated webform warning.
    $this->drupalGet('node/add/webform');
    $this->assertRaw('Webforms must first be <a href="' . $base_path . 'admin/structure/webform">created</a> before referencing them in the below form.');
  }

}
