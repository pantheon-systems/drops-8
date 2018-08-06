<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for email_confirm and email_multiple element.
 *
 * @group Webform
 */
class WebformElementEmailTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_email'];

  /**
   * Test email_confirm and email_multiple element.
   */
  public function testEmail() {

    /**************************************************************************/
    // email_multiple
    /**************************************************************************/

    // Check basic email multiple.
    $this->drupalGet('webform/test_element_email');
    $this->assertRaw('<label for="edit-email-multiple-basic">Multiple email addresses (basic)</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-multiple-basic" aria-describedby="edit-email-multiple-basic--description" type="text" id="edit-email-multiple-basic" name="email_multiple_basic" value="" size="60" class="form-text webform-email-multiple" />');
    $this->assertRaw('Multiple email addresses may be separated by commas.');

    // Check email multiple invalid second email address.
    $edit = [
      'email_multiple_basic' => 'example@example.com, Not a valid email address',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertRaw('The email address <em class="placeholder">Not a valid email address</em> is not valid.');

    // Check email multiple invalid token email address.
    $edit = [
      'email_multiple_basic' => 'example@example.com, [token]',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertRaw('The email address <em class="placeholder">[token]</em> is not valid.');

    // Check email multiple valid second email address.
    $edit = [
      'email_multiple_basic' => 'example@example.com, other@other.com',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertRaw("email_multiple_basic: 'example@example.com, other@other.com'");

    // Check email multiple valid token email address (via #allow_tokens).
    $edit = [
      'email_multiple_advanced' => 'example@example.com, [token]',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertRaw("email_multiple_advanced: 'example@example.com, [token]'");

    /**************************************************************************/
    // email_confirm
    /**************************************************************************/

    $this->drupalGet('webform/test_element_email');

    // Check basic email confirm.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-webform-email-confirm form-type-webform-email-confirm js-form-item-email-confirm-basic form-item-email-confirm-basic form-no-label">');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-basic-mail-1 form-item-email-confirm-basic-mail-1">');
    $this->assertRaw('<label for="edit-email-confirm-basic-mail-1">Email confirm basic</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-basic-mail-1" class="webform-email form-email" type="email" id="edit-email-confirm-basic-mail-1" name="email_confirm_basic[mail_1]" value="" size="60" maxlength="254" />');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-basic-mail-2 form-item-email-confirm-basic-mail-2">');
    $this->assertRaw('<label for="edit-email-confirm-basic-mail-2">Confirm email</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-basic-mail-2" class="webform-email-confirm form-email" type="email" id="edit-email-confirm-basic-mail-2" name="email_confirm_basic[mail_2]" value="" size="60" maxlength="254" />');

    // Check advanced email confirm w/ custom label.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-webform-email-confirm form-type-webform-email-confirm js-form-item-email-confirm-advanced form-item-email-confirm-advanced form-no-label">');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-advanced-mail-1 form-item-email-confirm-advanced-mail-1">');
    $this->assertRaw('<label for="edit-email-confirm-advanced-mail-1">Email confirm advanced</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-advanced-mail-1" aria-describedby="edit-email-confirm-advanced-mail-1--description" class="webform-email form-email" type="email" id="edit-email-confirm-advanced-mail-1" name="email_confirm_advanced[mail_1]" value="" size="60" maxlength="254" placeholder="Enter email address" />');
    $this->assertRaw('<div id="edit-email-confirm-advanced-mail-1--description" class="description">');
    $this->assertRaw('Please make sure to review your email address');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-advanced-mail-2 form-item-email-confirm-advanced-mail-2">');
    $this->assertRaw('<label for="edit-email-confirm-advanced-mail-2">Please confirm your email address</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-advanced-mail-2" aria-describedby="edit-email-confirm-advanced-mail-2--description" class="webform-email-confirm form-email" type="email" id="edit-email-confirm-advanced-mail-2" name="email_confirm_advanced[mail_2]" value="" size="60" maxlength="254" placeholder="Enter confirmation email address" />');
    $this->assertRaw('<div id="edit-email-confirm-advanced-mail-2--description" class="description">');
    $this->assertRaw('Please make sure to review your confirmation email address');

    // Check email confirm invalid email addresses.
    $edit = [
      'email_confirm_advanced[mail_1]' => 'Not a valid email address',
      'email_confirm_advanced[mail_2]' => 'Not a valid email address, again',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertRaw('The email address <em class="placeholder">Not a valid email address</em> is not valid.');
    $this->assertRaw('The email address <em class="placeholder">Not a valid email address, again</em> is not valid.');

    // Check email confirm non-matching email addresses.
    $edit = [
      'email_confirm_advanced[mail_1]' => 'example01@example.com',
      'email_confirm_advanced[mail_2]' => 'example02@example.com',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertRaw('The specified email addresses do not match.');

    // Check email confirm matching email addresses.
    $edit = [
      'email_confirm_advanced[mail_1]' => 'example@example.com',
      'email_confirm_advanced[mail_2]' => 'example@example.com',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertNoRaw('<li class="messages__item">The specified email addresses do not match.</li>');
    $this->assertRaw('email_confirm_advanced: example@example.com');

    // Check email confirm empty confirm email address.
    $edit = [
      'email_confirm_advanced[mail_1]' => '',
      'email_confirm_advanced[mail_2]' => '',
    ];
    $this->drupalPostForm('webform/test_element_email', $edit, t('Submit'));
    $this->assertNoRaw('<li class="messages__item">Confirm Email field is required.</li>');
  }

}
