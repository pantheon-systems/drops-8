<?php

namespace Drupal\captcha\Tests;

/**
 * Tests CAPTCHA Persistence.
 *
 * @group captcha
 */
class CaptchaPersistenceTestCase extends CaptchaBaseWebTestCase {

  /**
   * Set up the persistence and CAPTCHA settings.
   *
   * @param int $persistence
   *   The persistence value.
   */
  private function setUpPersistence($persistence) {
    $this->drupalLogin($this->adminUser);
    // Set persistence.
    $edit = ['persistence' => $persistence];
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH, $edit, 'Save configuration');
    // Log admin out.
    $this->drupalLogout();

    // Set the Test123 CAPTCHA on user register and comment form.
    // We have to do this with the function captcha_set_form_id_setting()
    // (because the CATCHA admin form does not show the Test123 option).
    // We also have to do this after all usage of the CAPTCHA admin form
    // (because posting the CAPTCHA admin form would set the CAPTCHA to 'none').
    captcha_set_form_id_setting('user_login_form', 'captcha/Test');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);
    captcha_set_form_id_setting('user_register_form', 'captcha/Test');
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
  }

  /**
   * Check if Captcha sid present in form.
   *
   * @param string $captcha_sid_initial
   *   Captcha SID token.
   */
  protected function assertPreservedCsid($captcha_sid_initial) {
    $captcha_sid = $this->getCaptchaSidFromForm();
    $this->assertEqual($captcha_sid_initial, $captcha_sid,
      "CAPTCHA session ID should be preserved (expected: $captcha_sid_initial, found: $captcha_sid).");
  }

  /**
   * Check if message about SID present.
   *
   * @param string $captcha_sid_initial
   *   Captcha SID token.
   */
  protected function assertDifferentCsid($captcha_sid_initial) {
    $captcha_sid = $this->getCaptchaSidFromForm();
    $this->assertNotEqual($captcha_sid_initial, $captcha_sid, "CAPTCHA session ID should be different.");
  }

  /**
   * Test persistence always.
   */
  public function testPersistenceAlways() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CAPTCHA_PERSISTENCE_SHOW_ALWAYS);

    // Go to login form and check if there is a CAPTCHA
    // on the login form (look for the title).
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->drupalPostForm(NULL, $edit, t('Log in'), [], [], self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();

    // Name and password were wrong, we should get an updated
    // form with a fresh CAPTCHA.
    $this->assertCaptchaPresence(TRUE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Post from again.
    $this->drupalPostForm(NULL, $edit, t('Log in'), [], [], self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    $this->assertPreservedCsid($captcha_sid_initial);
  }

  /**
   * Test persistence per form instance.
   */
  public function testPersistencePerFormInstance() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->drupalPostForm(NULL, $edit, t('Log in'), [], [], self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);

    // Check another form.
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Persistence per form type.
   */
  public function testPersistencePerFormType() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->drupalPostForm(NULL, $edit, t('Log in'), [], [], self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);

    // Check another form.
    /* @var \Drupal\captcha\Entity\CaptchaPoint $captcha_point */
    $captcha_point = \Drupal::entityTypeManager()
      ->getStorage('captcha_point')
      ->load('user_register_form');
    $captcha_point->enable()->save();
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(TRUE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

  /**
   * Test Persistence "Only once".
   */
  public function testPersistenceOnlyOnce() {
    // Set up of persistence and CAPTCHAs.
    $this->setUpPersistence(CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL);

    // Go to login form and check if there is a CAPTCHA on the login form.
    $this->drupalGet('<front>');
    $this->assertCaptchaPresence(TRUE);
    $captcha_sid_initial = $this->getCaptchaSidFromForm();

    // Try to with wrong user name and password, but correct CAPTCHA.
    $edit = [
      'name' => 'foobar',
      'pass' => 'bazlaz',
      'captcha_response' => 'Test 123',
    ];
    $this->drupalPostForm(NULL, $edit, t('Log in'), [], [], self::LOGIN_HTML_FORM_ID);
    // Check that there was no error message for the CAPTCHA.
    $this->assertCaptchaResponseAccepted();
    // There shouldn't be a CAPTCHA on the new form.
    $this->assertCaptchaPresence(FALSE);
    $this->assertPreservedCsid($captcha_sid_initial);

    // Start a new form instance/session.
    $this->drupalGet('node');
    $this->drupalGet('user');
    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);

    // Check another form.
    $this->drupalGet('user/register');
    $this->assertCaptchaPresence(FALSE);
    $this->assertDifferentCsid($captcha_sid_initial);
  }

}
