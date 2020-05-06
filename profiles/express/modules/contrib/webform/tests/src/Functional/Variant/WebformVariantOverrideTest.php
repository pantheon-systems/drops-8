<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant override.
 *
 * @group Webform
 */
class WebformVariantOverrideTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_override'];

  /**
   * Test variant override.
   */
  public function testVariantOverride() {
    $webform = Webform::load('test_variant_override');

    $this->drupalLogin($this->rootUser);

    // Check override settings enables preview.
    $this->drupalGet('/webform/test_variant_override');
    $this->assertNoRaw('<div class="webform-progress">');
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'settings']]);
    $this->assertRaw('<div class="webform-progress">');

    // Check override elements adds placeholder.
    $this->drupalGet('/webform/test_variant_override');
    $this->assertNoRaw('placeholder="This is a placeholder"');
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'elements']]);
    $this->assertRaw('placeholder="This is a placeholder"');

    // Check override handlers enables debug.
    $this->postSubmission($webform);
    $this->assertNoRaw('Submitted values are:');
    $this->postSubmission($webform, [], NULL, ['query' => ['_webform_variant[variant]' => 'handlers']]);
    $this->assertRaw('Submitted values are:');

    // Check override no results changes the confirmation message.
    $this->postSubmission($webform);
    $this->assertRaw('New submission added to Test: Variant override.');
    $this->assertNoRaw('No results were saved to the database.');
    $this->postSubmission($webform, [], NULL, ['query' => ['_webform_variant[variant]' => 'no_results']]);
    $this->assertNoRaw('New submission added to Test: Variant override.');
    $this->assertRaw('No results were saved to the database.');

    // Check missing variant instance displays a warning.
    $this->drupalGet('/webform/test_variant_override');
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'missing']]);
    $this->assertRaw("The 'missing' variant id is missing for the 'variant (variant)' variant type. <strong>No variant settings have been applied.</strong>");
  }

}
