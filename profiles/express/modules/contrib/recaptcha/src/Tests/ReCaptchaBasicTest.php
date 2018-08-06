<?php

namespace Drupal\recaptcha\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of reCAPTCHA module.
 *
 * @group reCAPTCHA
 *
 * @dependencies captcha
 */
class ReCaptchaBasicTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['recaptcha', 'captcha'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    module_load_include('inc', 'captcha');

    // Create a normal user.
    $permissions = [
      'access content',
    ];
    $this->normal_user = $this->drupalCreateUser($permissions);

    // Create an admin user.
    $permissions += [
      'administer CAPTCHA settings',
      'skip CAPTCHA',
      'administer permissions',
      'administer content types',
      'administer recaptcha',
    ];
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Test access to the administration page.
   */
  public function testReCaptchaAdminAccess() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/people/captcha/recaptcha');
    $this->assertNoText(t('Access denied'), 'Admin users should be able to access the reCAPTCHA admin page', 'reCAPTCHA');
    $this->drupalLogout();
  }

  /**
   * Test the reCAPTCHA settings form.
   */
  public function testReCaptchaAdminSettingsForm() {
    $this->drupalLogin($this->admin_user);

    $site_key = $this->randomMachineName(40);
    $secret_key = $this->randomMachineName(40);

    // Check form validation.
    $edit['recaptcha_site_key'] = '';
    $edit['recaptcha_secret_key'] = '';
    $this->drupalPostForm('admin/config/people/captcha/recaptcha', $edit, t('Save configuration'));

    $this->assertRaw(t('Site key field is required.'), '[testReCaptchaConfiguration]: Empty site key detected.');
    $this->assertRaw(t('Secret key field is required.'), '[testReCaptchaConfiguration]: Empty secret key detected.');

    // Save form with valid values.
    $edit['recaptcha_site_key'] = $site_key;
    $edit['recaptcha_secret_key'] = $secret_key;
    $edit['recaptcha_tabindex'] = 0;
    $this->drupalPostForm('admin/config/people/captcha/recaptcha', $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'), '[testReCaptchaConfiguration]: The configuration options have been saved.');

    $this->assertNoRaw(t('Site key field is required.'), '[testReCaptchaConfiguration]: Site key was not empty.');
    $this->assertNoRaw(t('Secret key field is required.'), '[testReCaptchaConfiguration]: Secret key was not empty.');
    $this->assertNoRaw(t('The tabindex must be an integer.'), '[testReCaptchaConfiguration]: Tab index had a valid input.');

    $this->drupalLogout();
  }

  /**
   * Testing the protection of the user login form.
   */
  public function testReCaptchaOnLoginForm() {
    $site_key = $this->randomMachineName(40);
    $secret_key = $this->randomMachineName(40);
    $grecaptcha = '<div class="g-recaptcha" data-sitekey="' . $site_key . '" data-theme="light" data-type="image"></div>';

    // Test if login works.
    $this->drupalLogin($this->normal_user);
    $this->drupalLogout();

    $this->drupalGet('user/login');
    $this->assertNoRaw($grecaptcha, '[testReCaptchaOnLoginForm]: reCAPTCHA is not shown on form.');

    // Enable 'captcha/Math' CAPTCHA on login form.
    captcha_set_form_id_setting('user_login_form', 'captcha/Math');

    $this->drupalGet('user/login');
    $this->assertNoRaw($grecaptcha, '[testReCaptchaOnLoginForm]: reCAPTCHA is not shown on form.');

    // Enable 'recaptcha/reCAPTCHA' on login form.
    captcha_set_form_id_setting('user_login_form', 'recaptcha/reCAPTCHA');
    $result = captcha_get_form_id_setting('user_login_form');
    $this->assertNotNull($result, 'A configuration has been found for CAPTCHA point: user_login_form', 'reCAPTCHA');
    //$this->assertEqual($result->module, 'recaptcha', 'reCAPTCHA module configured for CAPTCHA point: user_login_form', 'reCAPTCHA');
    //$this->assertEqual($result->getCaptchaType(), 'reCAPTCHA', 'reCAPTCHA type has been configured for CAPTCHA point: user_login_form', 'reCAPTCHA');
    $this->assertEqual($result->getCaptchaType(), 'recaptcha/reCAPTCHA', 'reCAPTCHA type has been configured for CAPTCHA point: user_login_form', 'reCAPTCHA');
    //$this->verbose($result->getCaptchaType());

    // Check if a Math CAPTCHA is still shown on the login form. The site key
    // and security key have not yet configured for reCAPTCHA. The module need
    // to fall back to math captcha.
    $this->drupalGet('user/login');
    $this->assertRaw(t('Math question'), '[testReCaptchaOnLoginForm]: Math CAPTCHA is shown on form.');

    // Configure site key and security key to show reCAPTCHA and no fall back.
    $this->config('recaptcha.settings')->set('site_key', $site_key)->save();
    $this->config('recaptcha.settings')->set('secret_key', $secret_key)->save();

    // Check if there is a reCAPTCHA on the login form.
    $this->drupalGet('user/login');
    $this->assertRaw($grecaptcha, '[testReCaptchaOnLoginForm]: reCAPTCHA is shown on form.');
    $this->assertRaw('<script src="https://www.google.com/recaptcha/api.js?hl=' . \Drupal::service('language_manager')->getCurrentLanguage()->getId() . '" async defer></script>', '[testReCaptchaOnLoginForm]: reCAPTCHA is shown on form.');
    $this->assertNoRaw($grecaptcha . '<noscript>', '[testReCaptchaOnLoginForm]: NoScript code is not enabled for the reCAPTCHA.');

    // Test if the fall back url is properly build and noscript code added.
    $this->config('recaptcha.settings')->set('widget.noscript', 1)->save();

    $this->drupalGet('user/login');
    $this->assertRaw($grecaptcha . "\n" . '<noscript>', '[testReCaptchaOnLoginForm]: NoScript for reCAPTCHA is shown on form.');
    $this->assertRaw('https://www.google.com/recaptcha/api/fallback?k=' . $site_key . '&amp;hl=' . \Drupal::service('language_manager')->getCurrentLanguage()->getId(), '[testReCaptchaOnLoginForm]: Fallback URL with IFRAME has been found.');

    // Check that data-size attribute does not exists.
    $this->config('recaptcha.settings')->set('widget.size', '')->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-size=:size]', [':class' => 'g-recaptcha', ':size' => 'small']);
    $this->assertFalse(!empty($element), 'Tag contains no data-size attribute.');

    // Check that data-size attribute exists.
    $this->config('recaptcha.settings')->set('widget.size', 'small')->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-size=:size]', [':class' => 'g-recaptcha', ':size' => 'small']);
    $this->assertTrue(!empty($element), 'Tag contains data-size attribute and value.');

    // Check that data-tabindex attribute does not exists.
    $this->config('recaptcha.settings')->set('widget.tabindex', 0)->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-tabindex=:index]', [':class' => 'g-recaptcha', ':index' => 0]);
    $this->assertFalse(!empty($element), 'Tag contains no data-tabindex attribute.');

    // Check that data-tabindex attribute exists.
    $this->config('recaptcha.settings')->set('widget.tabindex', 5)->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-tabindex=:index]', [':class' => 'g-recaptcha', ':index' => 5]);
    $this->assertTrue(!empty($element), 'Tag contains data-tabindex attribute and value.');

    // Try to log in, which should fail.
    $edit['name'] = $this->normal_user->getUsername();
    $edit['pass'] = $this->normal_user->getPassword();
    $edit['captcha_response'] = '?';

    $this->drupalPostForm('user/login', $edit, t('Log in'));
    // Check for error message.
    $this->assertText(t('The answer you entered for the CAPTCHA was not correct.'), 'CAPTCHA should block user login form', 'reCAPTCHA');

    // And make sure that user is not logged in: check for name and password
    // fields on "?q=user".
    $this->drupalGet('user/login');
    $this->assertField('name', t('Username field found.'), 'reCAPTCHA');
    $this->assertField('pass', t('Password field found.'), 'reCAPTCHA');
  }

}
