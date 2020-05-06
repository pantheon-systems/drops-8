<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for view element.
 *
 * @group Webform
 */
class WebformElementViewTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_ui', 'views', 'views_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_view'];

  /**
   * Test view element.
   */
  public function testView() {
    // Check that embedded view is render.
    $this->drupalGet('/webform/test_element_view');
    $this->assertCssSelect('.view-webform-submissions');
    $this->assertRaw('<div class="view-empty">');
    $this->assertRaw('No submissions available.');

    // Check that embedded view can't be edited.
    $admin_webform_account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($admin_webform_account);
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $this->assertRaw("Only users who can 'Administer views' or 'Edit webform source code' can update the view name, display id, and arguments.");
    $this->assertNoFieldByName('properties[name]');
    $this->assertNoFieldByName('properties[display_id]');

    // Check that embedded view can be edited.
    $admin_views_account = $this->drupalCreateUser(['administer webform', 'administer views']);
    $this->drupalLogin($admin_views_account);
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $this->assertNoRaw("Only users who can 'Administer views' or 'Edit webform source code' can update the view name, display id, and arguments.");
    $this->assertFieldByName('properties[name]');
    $this->assertFieldByName('properties[display_id]');

    // Check view name validation.
    $edit = ['properties[name]' => 'xxx'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_view/element/view/edit', $edit, t('Save'));
    $this->assertRaw('View <em class="placeholder">xxx</em> does not exist.');

    // Check view display id validation.
    $edit = ['properties[display_id]' => 'xxx'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_view/element/view/edit', $edit, t('Save'));
    $this->assertRaw('View display <em class="placeholder">xxx</em> does not exist.');

    // Check view exposed filter validation.
    $edit = ['properties[display_id]' => 'embed_administer'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_view/element/view/edit', $edit, t('Save'));
    $this->assertRaw('View display <em class="placeholder">embed_administer</em> has exposed filters which will break the webform.');

    // Check view exposed filter validation.
    $edit = [
      'properties[display_id]' => 'embed_administer',
      'properties[display_on]' => 'view',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_view/element/view/edit', $edit, t('Save'));
    $this->assertNoRaw('View display <em class="placeholder">embed_administer</em> has exposed filters which will break the webform.');
  }

}
