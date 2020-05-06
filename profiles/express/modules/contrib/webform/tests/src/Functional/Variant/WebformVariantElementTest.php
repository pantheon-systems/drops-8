<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant element.
 *
 * @group Webform
 */
class WebformVariantElementTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_ui', 'webform_test_variant'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_multiple'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->placeBlocks();
  }

  /**
   * Test variant element.
   */
  public function testVariantElement() {
    $variant_user = $this->drupalCreateUser(['administer webform', 'edit webform variants']);
    $admin_user = $this->drupalCreateUser(['administer webform']);

    /***************************************************************************/

    // Check that the variant element is visible to users with
    // 'edit webform variants' permission.
    $this->drupalLogin($variant_user);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add');
    $this->assertLink('Variant [EXPERIMENTAL]');

    // Check that the variant element is hidden to users without
    // 'edit webform variants' permission.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add');
    $this->assertNoLink('Variant [EXPERIMENTAL]');

    // Check that hidden variant element is still available.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $this->assertResponse(200);

    // Check that only the override variant plugins is available to all webforms.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $this->assertRaw('<option value="override">Override</option>');
    $this->assertNoRaw('<option value="test">Test</option>');

    // Check that only the test variant plugins is available to test_variant_*.
    // @see \Drupal\webform_test_variant\Plugin\WebformVariant\TestWebformVariant::isApplicable
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/element/add/webform_variant');
    $this->assertRaw('<option value="override">Override</option>');
    $this->assertRaw('<option value="test">Test</option>');

    // Login as variant user to display 'Variants' tab info messages.
    $this->drupalLogin($variant_user);

    // Check 'Variants' tab message is displayed.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $this->assertRaw("After clicking 'Save', the 'Variants' manage tab will be displayed. Use the 'Variants' manage tab to add and remove variants.");
    $this->assertNoText('Add and remove variants using the Variants manage tab.');

    // Check that 'Variants' tab is not visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertNoLink('Variants');

    // Add a variant element to contact form.
    $edit = [
      'key' => 'variant',
      'properties[title]' => '{variant_title}',
      'properties[variant]' => 'override',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/webform_variant', $edit, t('Save'));

    // Check that the 'Variants' tab is visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertLink('Variants');

    // Check that the 'Variants' tab message is displayed.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $this->assertNoRaw("After clicking 'Save', the 'Variants' manage tab will be displayed. Use the 'Variants' manage tab to add and remove variants.");
    $this->assertText('Add and remove variants using the Variants manage tab.');

    // Check that users missing the 'edit webform variants' permission
    // don't see any messages.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $this->assertNoText('Add and remove variants using the Variants manage tab.');

    // Check that the 'Variants' tab is also not visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertNoLink('Variants');

    // Check that the 'Variant type' can not be changed once variants have created.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/element/letter/edit');
    $this->assertNoRaw('<option value="override">Override</option>');
    $this->assertRaw('Override');
    $this->assertRaw('This variant is currently in-use. The variant type cannot be changed.');

    // Check that the letter element has 2 related variants.
    $webform = Webform::load('test_variant_multiple');
    $this->assertEqual(2, $webform->getVariants(NULL, NULL, 'letter')->count());

    // Delete the letter element and its related variants.
    $webform->deleteElement('letter');
    $webform->save();

    // Check that letter element now has 0 related variants.
    $this->assertEqual(0, $webform->getVariants(NULL, NULL, 'letter')->count());
  }

}
