<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for signature element.
 *
 * @group Webform
 */
class WebformElementSignatureTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_signature'];

  /**
   * Test signature element.
   */
  public function testSignature() {
    // Check signature display.
    $this->drupalGet('webform/test_element_signature');
    $this->assertRaw('<input data-drupal-selector="edit-signature-basic" aria-describedby="edit-signature-basic--description" type="hidden" name="signature_basic" value="" class="js-webform-signature form-webform-signature" /><div class="js-webform-signature-pad webform-signature-pad">');
    $this->assertRaw('<input type="submit" name="op" value="Reset" class="button js-form-submit form-submit" />');
    $this->assertRaw('<canvas></canvas>');
    $this->assertRaw('</div>');
    $this->assertRaw('<div id="edit-signature-basic--description" class="description">');
    $this->assertRaw('Sign above');
  }

}
