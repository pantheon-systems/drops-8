<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for email_multiple element.
 *
 * @group Webform
 */
class WebformElementEmailMultipleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_email_multiple'];

  /**
   * Test email_multiple element.
   */
  public function testEmailMultiple() {
    // Check basic email multiple.
    $this->drupalGet('/webform/test_element_email_multiple');
    $this->assertRaw('<label for="edit-email-multiple-basic">email_multiple_basic</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-multiple-basic" aria-describedby="edit-email-multiple-basic--description" type="text" id="edit-email-multiple-basic" name="email_multiple_basic" value="" size="60" class="form-text webform-email-multiple" />');
    $this->assertRaw('Multiple email addresses may be separated by commas.');

    // Check email multiple invalid second email address.
    $edit = [
      'email_multiple_basic' => 'example@example.com, Not a valid email address',
    ];
    $this->drupalPostForm('/webform/test_element_email_multiple', $edit, t('Submit'));
    $this->assertRaw('The email address <em class="placeholder">Not a valid email address</em> is not valid.');

    // Check email multiple invalid token email address.
    $edit = [
      'email_multiple_basic' => 'example@example.com, [token]',
    ];
    $this->drupalPostForm('/webform/test_element_email_multiple', $edit, t('Submit'));
    $this->assertRaw('The email address <em class="placeholder">[token]</em> is not valid.');

    // Check email multiple valid second email address.
    $edit = [
      'email_multiple_basic' => 'example@example.com, other@other.com',
    ];
    $this->drupalPostForm('/webform/test_element_email_multiple', $edit, t('Submit'));
    $this->assertRaw("email_multiple_basic: 'example@example.com, other@other.com'");

    // Check email multiple valid token email address (via #allow_tokens).
    $edit = [
      'email_multiple_advanced' => 'example@example.com, [token], [token1]@[token2].com',
    ];
    $this->drupalPostForm('/webform/test_element_email_multiple', $edit, t('Submit'));
    $this->assertRaw("email_multiple_advanced: 'example@example.com, [token], [token1]@[token2].com'");
  }

}
