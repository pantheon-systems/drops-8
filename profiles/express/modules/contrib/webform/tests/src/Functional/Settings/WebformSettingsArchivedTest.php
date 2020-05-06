<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform archived.
 *
 * @group Webform
 */
class WebformSettingsArchivedTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_node', 'webform_templates', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_archived'];

  /**
   * Test webform submission form archived.
   */
  public function testArchived() {
    global $base_path;

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_archived');

    // Check that archived webform is removed from webforms manage page.
    $this->drupalGet('/admin/structure/webform');
    $this->assertRaw('<td><a href="' . $base_path . 'form/contact">Contact</a></td>');
    $this->assertNoRaw('<td><a href="' . $base_path . 'form/test-form-archived">Test: Webform: Archive</a></td>');

    // Check that archived webform appears when archived filter selected.
    $this->drupalGet('/admin/structure/webform', ['query' => ['state' => 'archived']]);
    $this->assertNoRaw('<td><a href="' . $base_path . 'form/contact">Contact</a></td>');
    $this->assertRaw('<td><a href="' . $base_path . 'form/test-form-archived">Test: Webform: Archive</a></td>');

    // Check that archived webform displays archive message.
    $this->drupalGet('/form/test-form-archived');
    $this->assertRaw('This webform is <a href="' . $base_path . 'admin/structure/webform/manage/test_form_archived/settings">archived</a>');

    // Check that archived webform is remove webform select menu.
    $this->drupalGet('/node/add/webform');
    $this->assertRaw('<option value="contact">Contact</option>');
    $this->assertNoRaw('Test: Webform: Archive');

    // Check that selected archived webform is preserved in webform select menu.
    $this->drupalGet('/node/add/webform', ['query' => ['webform_id' => 'test_form_archived']]);
    $this->assertRaw('<option value="contact">Contact</option>');
    $this->assertRaw('<optgroup label="Archived"><option value="test_form_archived" selected="selected">Test: Webform: Archive</option></optgroup>');

    // Change the archived webform to be a template.
    $webform->set('template', TRUE);
    $webform->save();

    // Change archived webform to template.
    $this->drupalGet('/admin/structure/webform');
    $this->assertRaw('Contact');
    $this->assertNoRaw('Test: Webform: Archive');

    // Check that archived template with (Template) label appears when archived filter selected.
    $this->drupalGet('/admin/structure/webform', ['query' => ['state' => 'archived']]);
    $this->assertNoRaw('Contact');
    $this->assertRaw('<td><a href="' . $base_path . 'form/test-form-archived">Test: Webform: Archive</a> <b>(Template)</b></td>');

    // Check that archived template displays archive message
    // (not template message).
    $this->drupalGet('/form/test-form-archived');
    $this->assertRaw('This webform is <a href="' . $base_path . 'admin/structure/webform/manage/test_form_archived/settings">archived</a>');
  }

}
