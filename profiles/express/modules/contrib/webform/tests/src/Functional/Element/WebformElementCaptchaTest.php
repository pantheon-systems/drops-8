<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for CAPTCHA element.
 *
 * @group Webform
 */
class WebformElementCaptchaTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_captcha'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'captcha', 'image_captcha'];

  /**
   * Test CAPTCHA element.
   */
  public function testCaptcha() {
    $this->drupalGet('/webform/test_element_captcha');

    // Check default title and description.
    $this->assertRaw('<label for="edit-captcha-response" class="js-form-required form-required">Math question</label>');
    $this->assertRaw('Solve this simple math problem and enter the result. E.g. for 1+3, enter 4.');

    // Check CAPTCHA element custom title and description.
    $this->assertRaw('<label for="edit-captcha-response--4" class="js-form-required form-required">{captcha_math_title}</label>');
    $this->assertRaw('{captcha_math_description}');

    // Check CAPTCHA image refresh link.
    $this->assertLink('Get new captcha!');

    // Enable CAPTCHA admin mode.
    \Drupal::configFactory()
      ->getEditable('captcha.settings')
      ->set('administration_mode', TRUE)
      ->save();

    // Login root user.
    $this->drupalLogin($this->rootUser);

    // Check add CAPTCHA element text.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('CAPTCHA should be added as an element to this webform.');

    // Check replace CAPTCHA element text.
    $this->drupalGet('/webform/test_element_captcha');
    $this->assertNoRaw('/admin/structure/webform/manage/test_element_captcha/element/captcha/edit');
    $this->assertRaw('Untrusted users will see a CAPTCHA element on this webform.');

    // Install the Webform UI.
    \Drupal::service('module_installer')->install(['webform_ui']);

    // Check add CAPTCHA element text.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('Add CAPTCHA element to this webform for untrusted users.');

    // Check replace CAPTCHA element text.
    $this->drupalGet('/webform/test_element_captcha');
    $this->assertRaw('/admin/structure/webform/manage/test_element_captcha/element/captcha/edit');
    $this->assertRaw('Untrusted users will see a CAPTCHA element on this webform.');

    // Disable replace CAPTCHA admin mode.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('third_party_settings.captcha.replace_administration_mode', FALSE)
      ->save();

    // Check add CAPTCHA not replaced.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('Add CAPTCHA element to this webform for untrusted users.');
    $this->assertRaw('Place a CAPTCHA here for untrusted users.');

    // Enabled replace CAPTCHA admin mode and exclude the CAPTCHA element.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('element.excluded_elements', ['captcha' => 'captcha'])
      ->set('third_party_settings.captcha.replace_administration_mode', FALSE)
      ->save();

    // Check add CAPTCHA is still not replaced.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('Add CAPTCHA element to this webform for untrusted users.');
    $this->assertRaw('Place a CAPTCHA here for untrusted users.');
  }

}
