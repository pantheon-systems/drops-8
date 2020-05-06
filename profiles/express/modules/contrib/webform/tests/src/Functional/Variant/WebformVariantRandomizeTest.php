<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant randomize.
 *
 * @group Webform
 */
class WebformVariantRandomizeTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_randomize'];

  /**
   * Test variant randomize.
   */
  public function testVariantRandomize() {
    $webform = Webform::load('test_variant_randomize');

    // Check that randomize JavaScript is generated for 'a' and 'b'.
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertRaw('var variants = {"letter":["a","b"]};');

    // Disable variant 'a'.
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $variant_plugin */
    $variant_plugin = $webform->getVariant('a');
    $variant_plugin->disable();
    $webform->save();

    // Check that randomize JavaScript is generated for only 'b'.
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertRaw('var variants = {"letter":["b"]};');

    // Disable variant 'b'.
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $variant_plugin */
    $variant_plugin = $webform->getVariant('b');
    $variant_plugin->disable();
    $webform->save();

    // Check that no randomize JavaScript is generated because no variants
    // are enabled.
    $this->drupalGet('/webform/test_variant_randomize');
    $this->assertNoRaw('var variants');
  }

}
