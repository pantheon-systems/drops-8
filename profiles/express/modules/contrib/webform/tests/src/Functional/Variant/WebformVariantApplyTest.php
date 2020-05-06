<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform variant apply.
 *
 * @group Webform
 */
class WebformVariantApplyTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_variant_randomize',
    'test_variant_multiple',
  ];

  /**
   * Test variant apply.
   */
  public function testVariantApply() {
    $webform = $this->loadWebform('test_variant_randomize');

    $this->drupalLogin($this->rootUser);

    // Check apply single variant page title.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply');
    $this->assertRaw('Apply variant to the <em class="placeholder">Test: Variant randomize</em> webform?');

    // Check apply multiple variants page title.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants/apply');
    $this->assertRaw('>Apply the selected variants to the <em class="placeholder">Test: Variant multiple</em> webform?');

    // Check that no variant has not been applied.
    $this->assertEqual(2, $webform->getVariants()->count());
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertRaw('{X}');
    $this->drupalGet('/webform/test_variant_randomize', ['query' => ['letter' => 'a']]);
    $this->assertNoRaw('{X}');
    $this->assertRaw('[A]');

    // Check access denied error when trying to apply non-existent variant.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', ['query' => ['variant_id' => 'c']]);
    $this->assertResponse(403);

    // Check access allowed when trying to apply existing 'a' variant.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', ['query' => ['variant_id' => 'a']]);
    $this->assertResponse(200);

    // Check variant select menu is not visible when variant is specified.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', ['query' => ['variant_id' => 'a']]);
    $this->assertElementNotPresent('#edit-variants-letter');

    // Check variant select menu is visible when no variant is specified.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply');
    $this->assertElementPresent('#edit-variants-letter');

    // Apply 'a' variant.
    $edit = ['delete' => 'none'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $edit, t('Apply'), ['query' => ['variant_id' => 'a']]);
    $webform = $this->reloadWebform('test_variant_randomize');

    // Check that the 'a' variant has been applied and no variants have been deleted.
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertNoRaw('{X}');
    $this->assertRaw('[A]');
    $this->assertTrue($webform->getVariants()->has('a'));
    $this->assertEqual(2, $webform->getVariants()->count());

    // Disable the 'b' variant.
    $variant = $webform->getVariant('b');
    $variant->disable();
    $webform->save();

    // Apply the 'b' variant which is disabled.
    $edit = ['delete' => 'none'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $edit, t('Apply'), ['query' => ['variant_id' => 'b']]);
    $webform = $this->reloadWebform('test_variant_randomize');
    $this->assertNoRaw('{X}');
    $this->assertRaw('[B]');
    $this->assertTrue($webform->getVariants()->has('b'));
    $this->assertEqual(2, $webform->getVariants()->count());

    // Apply and delete the 'a' variant.
    $edit = ['delete' => 'selected'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $edit, t('Apply'), ['query' => ['variant_id' => 'a']]);
    $webform = $this->reloadWebform('test_variant_randomize');

    // Check that the 'a' variant has been applied and no variants have been deleted.
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertNoRaw('{X}');
    $this->assertRaw('[A]');
    $this->assertFalse($webform->getVariants()->has('a'));
    $this->assertEqual(1, $webform->getVariants()->count());

    // Apply the 'b' variant and delete all variants.
    $edit = ['delete' => 'all'];
    $this->drupalPostForm('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $edit, t('Apply'), ['query' => ['variant_id' => 'b']]);
    $webform = $this->reloadWebform('test_variant_randomize');

    // Check that the 'b' variant has been applied and all variants have been deleted.
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertNoRaw('{X}');
    $this->assertRaw('[B]');
    $this->assertFalse($webform->getVariants()->has('b'));
    $this->assertEqual(0, $webform->getVariants()->count());
  }

}
