<?php

namespace Drupal\webform\Tests;

use Drupal\webform_node\Tests\WebformNodeTestBase;

/**
 * Tests for webform alter hooks.
 *
 * @group Webform
 */
class WebformAlterHooksTest extends WebformNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_node', 'webform_test_alter_hooks'];

  /**
   * Tests webform alter hooks.
   */
  public function testWebformAlterHooks() {
    // Check webform alter hooks.
    $this->drupalGet('webform/contact');
    $this->assertRaw("hook_form_alter(): 'webform_submission_contact_add_form' executed.");
    $this->assertRaw("hook_form_webform_submission_form_alter(): 'webform_submission_contact_add_form' executed.");
    $this->assertRaw("hook_form_webform_submission_WEBFORM_ID_form_alter(): 'webform_submission_contact_add_form' executed.");

    // Check webform element alter hooks.
    $this->assertRaw("hook_webform_element_alter(): 'name' executed.");
    $this->assertRaw("hook_webform_element_alter(): 'email' executed.");
    $this->assertRaw("hook_webform_element_ELEMENT_TYPE_alter(): 'email' executed.");
    $this->assertRaw("hook_webform_element_alter(): 'subject' executed.");
    $this->assertRaw("hook_webform_element_alter(): 'message' executed.");

    // Check webform node alter hooks.
    $webform_node = $this->createWebformNode('contact');
    $nid = $webform_node->id();
    $this->drupalGet("node/$nid");
    $this->assertRaw("hook_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
    $this->assertRaw("hook_form_webform_submission_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
    $this->assertRaw("hook_form_webform_submission_WEBFORM_ID_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
  }

}
