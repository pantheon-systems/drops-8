<?php

namespace Drupal\captcha\Tests;

use Drupal\captcha\Entity\CaptchaPoint;
use Drupal\Core\Url;

/**
 * Tests CAPTCHA admin settings.
 *
 * @group captcha
 */
class CaptchaAdminTestCase extends CaptchaBaseWebTestCase {

  /**
   * Test access to the admin pages.
   */
  public function testAdminAccess() {
    $this->drupalLogin($this->normalUser);
    $this->drupalGet(self::CAPTCHA_ADMIN_PATH);
    // @TODO do we need this ?
    // file_put_contents('tmp.simpletest.html', $this->drupalGetContent());
    $this->assertText(t('Access denied'), 'Normal users should not be able to access the CAPTCHA admin pages', 'CAPTCHA');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::CAPTCHA_ADMIN_PATH);
    $this->assertNoText(t('Access denied'), 'Admin users should be able to access the CAPTCHA admin pages', 'CAPTCHA');
  }

  /**
   * Test the CAPTCHA point setting getter/setter.
   */
  public function testCaptchaPointSettingGetterAndSetter() {
    $comment_form_id = self::COMMENT_FORM_ID;
    captcha_set_form_id_setting($comment_form_id, 'test');
    /* @var CaptchaPoint $result */
    $result = captcha_get_form_id_setting($comment_form_id);
    $this->assertNotNull($result, 'CAPTCHA exists', 'CAPTCHA');
    $this->assertEqual($result->getCaptchaType(), 'test', 'CAPTCHA type: default', 'CAPTCHA');
    $result = captcha_get_form_id_setting($comment_form_id, TRUE);
    $this->assertNotNull($result, 'CAPTCHA exists', 'CAPTCHA');
    $this->assertEqual($result, 'test', 'Setting and symbolic getting CAPTCHA point: "test"', 'CAPTCHA');

    // Set to 'default'.
    captcha_set_form_id_setting($comment_form_id, 'default');
    $this->config('captcha.settings')
      ->set('default_challenge', 'foo/bar')
      ->save();
    $result = captcha_get_form_id_setting($comment_form_id);
    $this->assertNotNull($result, 'CAPTCHA exists', 'CAPTCHA');
    $this->assertEqual($result->getCaptchaType(), 'foo/bar', 'Setting and getting CAPTCHA point: default', 'CAPTCHA');
    $result = captcha_get_form_id_setting($comment_form_id, TRUE);
    $this->assertNotNull($result, 'Setting and symbolic getting CAPTCHA point: "default"', 'CAPTCHA');
    $this->assertEqual($result, 'foo/bar', 'Setting and symbolic getting CAPTCHA point: default', 'CAPTCHA');

    // Set to 'baz/boo'.
    captcha_set_form_id_setting($comment_form_id, 'baz/boo');
    $result = captcha_get_form_id_setting($comment_form_id);
    $this->assertNotNull($result, 'CAPTCHA exists', 'CAPTCHA');
    $this->assertEqual($result->getCaptchaType(), 'baz/boo', 'Setting and getting CAPTCHA point: baz/boo', 'CAPTCHA');
    $result = captcha_get_form_id_setting($comment_form_id, TRUE);
    $this->assertEqual($result, 'baz/boo', 'Setting and symbolic getting CAPTCHA point: "baz/boo"', 'CAPTCHA');

    // Set to NULL (which should delete the CAPTCHA point setting entry).
    captcha_set_form_id_setting($comment_form_id, NULL);
    $result = captcha_get_form_id_setting($comment_form_id);
    $this->assertNotNull($result, 'CAPTCHA exists', 'CAPTCHA');
    $this->assertEqual($result->getCaptchaType(), 'foo/bar', 'Setting and getting CAPTCHA point: NULL', 'CAPTCHA');
    $result = captcha_get_form_id_setting($comment_form_id, TRUE);
    $this->assertNotNull($result, 'CAPTCHA exists', 'CAPTCHA');

    // Set with object.
    $captcha_type = 'baba/fofo';
    captcha_set_form_id_setting($comment_form_id, $captcha_type);

    $result = captcha_get_form_id_setting($comment_form_id);
    $this->assertNotNull($result, 'Setting and getting CAPTCHA point: baba/fofo', 'CAPTCHA');
    // $this->assertEqual($result->module, 'baba', 'Setting and getting
    // CAPTCHA point: baba/fofo', 'CAPTCHA');.
    $this->assertEqual($result->getCaptchaType(), 'baba/fofo', 'Setting and getting CAPTCHA point: baba/fofo', 'CAPTCHA');
    $result = captcha_get_form_id_setting($comment_form_id, TRUE);
    $this->assertEqual($result, 'baba/fofo', 'Setting and symbolic getting CAPTCHA point: "baba/fofo"', 'CAPTCHA');
  }

  /**
   * Helper function for checking CAPTCHA setting of a form.
   *
   * @param string $form_id
   *   The form_id of the form to investigate.
   * @param string $challenge_type
   *   What the challenge type should be:
   *   NULL, 'default' or something like 'captcha/Math'.
   */
  protected function assertCaptchaSetting($form_id, $challenge_type) {
    $result = captcha_get_form_id_setting(self::COMMENT_FORM_ID, TRUE);
    $this->assertEqual($result, $challenge_type,
      t('Check CAPTCHA setting for form: expected: @expected, received: @received.',
        [
          '@expected' => var_export($challenge_type, TRUE),
          '@received' => var_export($result, TRUE),
        ]),
      'CAPTCHA');
  }

  /**
   * Testing of the CAPTCHA administration links.
   */
  public function testCaptchaAdminLinks() {
    $this->drupalLogin($this->adminUser);

    // Enable CAPTCHA administration links.
    $edit = [
      'administration_mode' => TRUE,
    ];

    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH, $edit, t('Save configuration'));

    // Create a node with comments enabled.
    $node = $this->drupalCreateNode();

    // Go to node page.
    $this->drupalGet('node/' . $node->id());

    // Click the add new comment link.
    $this->clickLink(t('Add new comment'));
    $add_comment_url = $this->getUrl();

    // Remove fragment part from comment URL to avoid
    // problems with later asserts.
    $add_comment_url = strtok($add_comment_url, "#");

    // Click the CAPTCHA admin link to enable a challenge.
    $this->clickLink(t('Place a CAPTCHA here for untrusted users.'));

    // Enable Math CAPTCHA.
    $edit = ['captchaType' => 'captcha/Math'];
    $this->drupalPostForm($this->getUrl(), $edit, t('Save'));
    // Check if returned to original comment form.
    $this->assertUrl($add_comment_url, [],
      'After setting CAPTCHA with CAPTCHA admin links: should return to original form.', 'CAPTCHA');

    // Check if CAPTCHA was successfully enabled
    // (on CAPTCHA admin links fieldset).
    $this->assertText(t('CAPTCHA: challenge "@type" enabled', ['@type' => $edit['captchaType']]),
      'Enable a challenge through the CAPTCHA admin links', 'CAPTCHA');

    // Check if CAPTCHA was successfully enabled (through API).
    $this->assertCaptchaSetting(self::COMMENT_FORM_ID, 'captcha/Math');

    // Edit challenge type through CAPTCHA admin links.
    $this->clickLink(t('change'));

    // Enable Math CAPTCHA.
    $edit = ['captchaType' => 'default'];
    $this->drupalPostForm($this->getUrl(), $edit, t('Save'));

    // Check if returned to original comment form.
    $this->assertEqual($add_comment_url, $this->getUrl(),
      'After editing challenge type CAPTCHA admin links: should return to original form.', 'CAPTCHA');

    // Check if CAPTCHA was successfully changed
    // (on CAPTCHA admin links fieldset).
    // This is actually the same as the previous setting because
    // the captcha/Math is the default for the default challenge.
    // TODO Make sure the edit is a real change.
    $this->assertText(t('CAPTCHA: challenge "@type" enabled', ['@type' => $edit['captchaType']]),
      'Enable a challenge through the CAPTCHA admin links', 'CAPTCHA');
    // Check if CAPTCHA was successfully edited (through API).
    $this->assertCaptchaSetting(self::COMMENT_FORM_ID, 'default');

    // Disable challenge through CAPTCHA admin links.
    $this->drupalGet(Url::fromRoute('entity.captcha_point.disable', ['captcha_point' => self::COMMENT_FORM_ID]));
    $this->drupalPostForm(NULL, [], t('Disable'));

    // Check if returned to captcha point list.
    global $base_url;
    $this->assertEqual($base_url . '/admin/config/people/captcha/captcha-points', $this->getUrl(),
      'After disabling challenge in CAPTCHA admin: should return to captcha point list.', 'CAPTCHA');

    // Check if CAPTCHA was successfully disabled
    // (on CAPTCHA admin links fieldset).
    $this->assertRaw(t('Captcha point %form_id has been disabled.', ['%form_id' => self::COMMENT_FORM_ID]),
      'Disable challenge through the CAPTCHA admin links', 'CAPTCHA');
  }

  /**
   * Test untrusted user posting.
   */
  public function testUntrustedUserPosting() {
    // Set CAPTCHA on comment form.
    captcha_set_form_id_setting(self::COMMENT_FORM_ID, 'captcha/Math');

    // Create a node with comments enabled.
    $node = $this->drupalCreateNode();

    // Log in as normal (untrusted) user.
    $this->drupalLogin($this->normalUser);

    // Go to node page and click the "add comment" link.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink(t('Add new comment'));
    $add_comment_url = $this->getUrl();

    // Check if CAPTCHA is visible on form.
    $this->assertCaptchaPresence(TRUE);
    // Try to post a comment with wrong answer.
    $edit = $this->getCommentFormValues();
    $edit['captcha_response'] = 'xx';
    $this->drupalPostForm($add_comment_url, $edit, t('Preview'));
    $this->assertText(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE,
      'wrong CAPTCHA should block form submission.', 'CAPTCHA');
  }

  /**
   * Test XSS vulnerability on CAPTCHA description.
   */
  public function testXssOnCaptchaDescription() {
    // Set CAPTCHA on user register form.
    captcha_set_form_id_setting('user_register', 'captcha/Math');

    // Put JavaScript snippet in CAPTCHA description.
    $this->drupalLogin($this->adminUser);
    $xss = '<script type="text/javascript">alert("xss")</script>';
    $edit = ['description' => $xss];
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH, $edit, 'Save configuration');

    // Visit user register form and check if JavaScript snippet is there.
    $this->drupalLogout();
    $this->drupalGet('user/register');
    $this->assertNoRaw($xss, 'JavaScript should not be allowed in CAPTCHA description.', 'CAPTCHA');
  }

  /**
   * Test the CAPTCHA placement clearing.
   */
  public function testCaptchaPlacementCacheClearing() {
    // Set CAPTCHA on user register form.
    captcha_set_form_id_setting('user_register_form', 'captcha/Math');
    // Visit user register form to fill the CAPTCHA placement cache.
    $this->drupalGet('user/register');
    // Check if there is CAPTCHA placement cache.
    $placement_map = $this->container->get('cache.default')
      ->get('captcha_placement_map_cache');
    $this->assertNotNull($placement_map, 'CAPTCHA placement cache should be set.');
    // Clear the cache.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH, [], t('Clear the CAPTCHA placement cache'));
    // Check that the placement cache is unset.
    $placement_map = $this->container->get('cache.default')
      ->get('captcha_placement_map_cache');
    $this->assertFalse($placement_map, 'CAPTCHA placement cache should be unset after cache clear.');
  }

  /**
   * Helper function to get CAPTCHA point setting straight from the database.
   *
   * @param string $form_id
   *   Form machine ID.
   *
   * @return \Drupal\captcha\Entity\CaptchaPoint
   *    CaptchaPoint with mysql query result.
   */
  protected function getCaptchaPointSettingFromDatabase($form_id) {
    $ids = \Drupal::entityQuery('captcha_point')
      ->condition('formId', $form_id)
      ->execute();
    return $ids ? CaptchaPoint::load(reset($ids)) : NULL;
  }

  /**
   * Method for testing the CAPTCHA point administration.
   */
  public function testCaptchaPointAdministration() {
    // Generate CAPTCHA point data:
    // Drupal form ID should consist of lowercase alphanumerics and underscore).
    $captcha_point_form_id = 'form_' . strtolower($this->randomMachineName(32));
    // The Math CAPTCHA by the CAPTCHA module is always available,
    // so let's use it.
    $captcha_point_module = 'captcha';
    $captcha_point_type = 'Math';

    // Log in as admin.
    $this->drupalLogin($this->adminUser);
    $label = 'TEST';

    // Try and set CAPTCHA point without the #required label. Should fail.
    $form_values = [
      'formId' => $captcha_point_form_id,
      'captchaType' => $captcha_point_module . '/' . $captcha_point_type,
    ];
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/add', $form_values, t('Save'));
    $this->assertText(t('Form ID field is required.'));

    // Set CAPTCHA point through admin/user/captcha/captcha/captcha_point.
    $form_values['label'] = $label;
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/add', $form_values, t('Save'));
    $this->assertRaw(t('Captcha Point for %label form was created.', ['%label' => $captcha_point_form_id]));

    // Check in database.
    /* @var CaptchaPoint result */
    $result = $this->getCaptchaPointSettingFromDatabase($captcha_point_form_id);
    $this->assertEqual($result->captchaType, $captcha_point_module . '/' . $captcha_point_type,
      'Enabled CAPTCHA point should have module and type set');

    // Disable CAPTCHA point again.
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/' . $captcha_point_form_id . '/disable', [], t('Disable'));
    $this->assertRaw(t('Captcha point %label has been disabled.', ['%label' => $label]), 'Disabling of CAPTCHA point');

    // Check in database.
    $result = $this->getCaptchaPointSettingFromDatabase($captcha_point_form_id);

    // Set CAPTCHA point via admin/user/captcha/captcha/captcha_point/$form_id.
    $form_values = [
      'captchaType' => $captcha_point_module . '/' . $captcha_point_type,
    ];
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/' . $captcha_point_form_id, $form_values, t('Save'));
    $this->assertRaw(t('Captcha Point for %form_id form was updated.', ['%form_id' => $captcha_point_form_id]), 'Saving of CAPTCHA point settings');

    // Check in database.
    $result = $this->getCaptchaPointSettingFromDatabase($captcha_point_form_id);
    $this->assertEqual($result->captchaType, $captcha_point_module . '/' . $captcha_point_type,
      'Enabled CAPTCHA point should have module and type set');

    // Delete CAPTCHA point.
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/' . $captcha_point_form_id . '/delete', [], t('Delete'));
    $this->assertRaw(t('Captcha point %label has been deleted.', ['%label' => $label]),
      'Deleting of CAPTCHA point');

    $result = $this->getCaptchaPointSettingFromDatabase($captcha_point_form_id);
    $this->assertFalse($result, 'Deleted CAPTCHA point should be in database');
  }

  /**
   * Method for testing the CAPTCHA point administration.
   */
  public function testCaptchaPointAdministrationByNonAdmin() {
    // First add a CAPTCHA point (as admin).
    $captcha_point_form_id = 'form_' . strtolower($this->randomMachineName(32));
    $captcha_point_module = 'captcha';
    $captcha_point_type = 'Math';
    $label = 'TEST_2';

    $this->drupalLogin($this->adminUser);

    $form_values = [
      'label' => $label,
      'formId' => $captcha_point_form_id,
      'captchaType' => $captcha_point_module . '/' . $captcha_point_type,
    ];
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/add', $form_values, 'Save');
    $this->assertRaw(t('Captcha Point for %form_id form was created.', ['%form_id' => $captcha_point_form_id]));

    // Switch from admin to non-admin.
    $this->drupalLogin($this->normalUser);

    // Try to set CAPTCHA point
    // through admin/user/captcha/captcha/captcha_point.
    $this->drupalGet(self::CAPTCHA_ADMIN_PATH . '/captcha-points');
    $this->assertText(t('You are not authorized to access this page.'),
      'Non admin should not be able to set a CAPTCHA point');

    // Try to disable the CAPTCHA point.
    $this->drupalGet(self::CAPTCHA_ADMIN_PATH . '/captcha-points/' . $captcha_point_form_id . '/disable');
    $this->assertText(t('You are not authorized to access this page.'),
      'Non admin should not be able to disable a CAPTCHA point');

    // Try to delete the CAPTCHA point.
    $this->drupalGet(self::CAPTCHA_ADMIN_PATH . '/captcha-points/' . $captcha_point_form_id . '/delete');
    $this->assertText(t('You are not authorized to access this page.'),
      'Non admin should not be able to delete a CAPTCHA point');

    // Switch from nonadmin to admin again.
    $this->drupalLogin($this->adminUser);

    // Check if original CAPTCHA point still exists in database.
    $result = $this->getCaptchaPointSettingFromDatabase($captcha_point_form_id);
    $this->assertEqual($result->captchaType, $captcha_point_module . '/' . $captcha_point_type, 'Enabled CAPTCHA point should have module and type set');

    // Delete captcha point.
    $this->drupalPostForm(self::CAPTCHA_ADMIN_PATH . '/captcha-points/' . $captcha_point_form_id . '/delete', [], 'Delete');
    $this->assertRaw(t('Captcha point %label has been deleted.', ['%label' => $label]), 'Disabling of CAPTCHA point');
  }

}
