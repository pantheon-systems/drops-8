<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for updating webform submission using tokenized URL.
 *
 * @group Webform
 */
class WebformSubmissionTokenUpdateTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['token'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_token_view_update'];

  /**
   * Test updating webform submission using tokenized URL.
   */
  public function testTokenUpdateTest() {
    $normal_user = $this->drupalCreateUser();

    $webform = Webform::load('test_token_view_update');

    /**************************************************************************/

    // Post test submission.
    $this->drupalLogin($this->rootUser);
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);
    // Check confirmation page's URL token.
    $token_url = $webform_submission->getTokenUrl();
    $link_label = $token_url->setAbsolute(FALSE)->toString();
    $link_url = $token_url->setAbsolute(TRUE)->toString();
    $this->assertRaw('<a href="' . $link_url . '">' . $link_label . '</a>');

    /* View */

    // Check token view access allowed.
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('view'));
    $this->assertResponse(200);
    $this->assertRaw('Submission information');
    $this->assertRaw('<label>textfield</label>');

    // Check token view access denied.
    $webform->setSetting('token_view', FALSE)->save();
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('view'));
    $this->assertResponse(403);
    $this->assertNoRaw('Submission information');
    $this->assertNoRaw('<label>textfield</label>');

    /* Update */

    // Check token update access allowed.
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertRaw('Submission information');
    $this->assertFieldByName('textfield', $webform_submission->getElementData('textfield'));

    // Check token update not autoload.
    $webform->setSetting('token_update', FALSE)->save();
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertNoRaw('Submission information');
    $this->assertNoFieldByName('textfield', $webform_submission->getElementData('textfield'));

    // Logout and switch to anonymous user.
    $this->drupalLogout();

    // Set access to authenticated only and reenabled tokenized URL.
    $access = $webform->getAccessRules();
    $access['create']['roles'] = ['authenticated'];
    $webform->setAccessRules($access);
    $webform
      ->setSetting('token_view', TRUE)
      ->setSetting('token_update', TRUE)
      ->save();

    // Check that access is denied for anonymous user.
    $this->drupalGet('/webform/test_token_view_update');
    $this->assertResponse(403);

    // Check token view access allowed for anonymous user.
    $this->drupalGet($webform_submission->getTokenUrl('view'));
    $this->assertResponse(200);

    // Check token update access allowed for anonymous user.
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
  }

}
