<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for email_confirm element.
 *
 * @group Webform
 */
class WebformElementEmailConfirmTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_email_confirm'];

  /**
   * Test email_confirm element.
   */
  public function testEmailConfirm() {
    $this->drupalGet('/webform/test_element_email_confirm');

    // Check basic email confirm.
    $this->assertRaw('<fieldset id="edit-email-confirm-basic--wrapper" class="webform-email-confirm--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-webform-email-confirm webform-type-webform-email-confirm js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="visually-hidden fieldset-legend">email_confirm_basic</span>');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-basic-mail-1 form-item-email-confirm-basic-mail-1">');
    $this->assertRaw('<label for="edit-email-confirm-basic-mail-1">email_confirm_basic</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-basic-mail-1" class="webform-email form-email" type="email" id="edit-email-confirm-basic-mail-1" name="email_confirm_basic[mail_1]" value="" size="60" maxlength="254" />');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-basic-mail-2 form-item-email-confirm-basic-mail-2">');
    $this->assertRaw('<label for="edit-email-confirm-basic-mail-2">Confirm email</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-basic-mail-2" class="webform-email-confirm form-email" type="email" id="edit-email-confirm-basic-mail-2" name="email_confirm_basic[mail_2]" value="" size="60" maxlength="254" />');

    // Check advanced email confirm w/ custom label.
    $this->assertRaw('<fieldset id="edit-email-confirm-advanced--wrapper" class="webform-email-confirm--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-webform-email-confirm webform-type-webform-email-confirm js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="visually-hidden fieldset-legend">Email address</span>');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-advanced-mail-1 form-item-email-confirm-advanced-mail-1">');
    $this->assertRaw('<label for="edit-email-confirm-advanced-mail-1">Email address</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-advanced-mail-1" aria-describedby="edit-email-confirm-advanced-mail-1--description" class="webform-email form-email" type="email" id="edit-email-confirm-advanced-mail-1" name="email_confirm_advanced[mail_1]" value="" size="60" maxlength="254" placeholder="Enter email address" />');
    $this->assertRaw('<div id="edit-email-confirm-advanced-mail-1--description" class="webform-element-description">Please make sure to review your email address</div>');
    $this->assertRaw('<div class="js-form-item form-item js-form-type-email form-type-email js-form-item-email-confirm-advanced-mail-2 form-item-email-confirm-advanced-mail-2">');
    $this->assertRaw('<label for="edit-email-confirm-advanced-mail-2">Please confirm your email address</label>');
    $this->assertRaw('<input data-drupal-selector="edit-email-confirm-advanced-mail-2" aria-describedby="edit-email-confirm-advanced-mail-2--description" class="webform-email-confirm form-email" type="email" id="edit-email-confirm-advanced-mail-2" name="email_confirm_advanced[mail_2]" value="" size="60" maxlength="254" placeholder="Enter confirmation email address" />');
    $this->assertRaw('<div id="edit-email-confirm-advanced-mail-2--description" class="webform-element-description">Please make sure to review your confirmation email address</div>');

    // Check flexbox.
    $this->assertRaw('<div data-drupal-selector="edit-email-confirm-flexbox-flexbox" class="webform-flexbox js-webform-flexbox js-form-wrapper form-wrapper" id="edit-email-confirm-flexbox-flexbox"><div class="webform-flex webform-flex--1"><div class="webform-flex--container">');

    // Check flexbox submit.
    $edit = [
      'email_confirm_flexbox[mail_1]' => 'example01@example.com',
      'email_confirm_flexbox[mail_2]' => 'example02@example.com',
    ];
    $this->drupalPostForm('/webform/test_element_email_confirm', $edit, t('Submit'));
    $this->assertRaw('The specified email addresses do not match.');

    $edit = [
      'email_confirm_flexbox[mail_1]' => 'example@example.com',
      'email_confirm_flexbox[mail_2]' => 'example@example.com',
    ];
    $this->drupalPostForm('/webform/test_element_email_confirm', $edit, t('Submit'));
    $this->assertRaw("email_confirm_basic: ''
email_confirm_advanced: ''
email_confirm_pattern: ''
email_confirm_required: example@example.com
email_confirm_flexbox: example@example.com");

    // Check email confirm invalid email addresses.
    $edit = [
      'email_confirm_advanced[mail_1]' => 'Not a valid email address',
      'email_confirm_advanced[mail_2]' => 'Not a valid email address, again',
    ];
    $this->drupalPostForm('/webform/test_element_email_confirm', $edit, t('Submit'));
    $this->assertRaw('The email address <em class="placeholder">Not a valid email address</em> is not valid.');
    $this->assertRaw('The email address <em class="placeholder">Not a valid email address, again</em> is not valid.');

    // Check email confirm non-matching email addresses.
    $edit = [
      'email_confirm_advanced[mail_1]' => 'example01@example.com',
      'email_confirm_advanced[mail_2]' => 'example02@example.com',
    ];
    $this->drupalPostForm('/webform/test_element_email_confirm', $edit, t('Submit'));
    $this->assertRaw('The specified email addresses do not match.');

    // Check email confirm matching email addresses.
    $edit = [
      'email_confirm_advanced[mail_1]' => 'example@example.com',
      'email_confirm_advanced[mail_2]' => 'example@example.com',
    ];
    $this->drupalPostForm('/webform/test_element_email_confirm', $edit, t('Submit'));
    $this->assertNoRaw('<li class="messages__item">The specified email addresses do not match.</li>');
    $this->assertRaw('email_confirm_advanced: example@example.com');

    // Check email confirm empty confirm email address.
    $edit = [
      'email_confirm_advanced[mail_1]' => '',
      'email_confirm_advanced[mail_2]' => '',
    ];
    $this->drupalPostForm('/webform/test_element_email_confirm', $edit, t('Submit'));
    $this->assertNoRaw('<li class="messages__item">Confirm Email field is required.</li>');
  }

}
