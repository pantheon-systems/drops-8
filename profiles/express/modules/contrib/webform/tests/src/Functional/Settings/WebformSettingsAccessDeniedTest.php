<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\WebformInterface;

/**
 * Tests for access denied webform and submissions.
 *
 * @group Webform
 */
class WebformSettingsAccessDeniedTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_access_denied'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Place blocks.
    $this->placeBlocks();
  }

  /**
   * Tests webform access denied setting.
   */
  public function testWebformAccessDenied() {
    $webform = Webform::load('test_form_access_denied');
    $webform_edit_route_url = Url::fromRoute('entity.webform.edit_form', [
      'webform' => $webform->id(),
    ]);

    /**************************************************************************/
    // Redirect.
    /**************************************************************************/

    // Set access denied to redirect with message.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_LOGIN);
    $webform->save();

    // Check form message is displayed and user is redirected to the login form.
    $this->drupalGet('/admin/structure/webform/manage/test_form_access_denied');
    $this->assertRaw('Please login to access <b>Test: Webform: Access Denied</b>.');
    $route_options = [
      'query' => [
        'destination' => $webform_edit_route_url->toString(),
      ],
    ];
    $this->assertUrl(Url::fromRoute('user.login', [], $route_options));

    /**************************************************************************/
    // Default.
    /**************************************************************************/

    // Set default access denied page.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_DEFAULT);
    $webform->save();

    // Check default access denied page.
    $this->drupalGet('/admin/structure/webform/manage/test_form_access_denied');
    $this->assertRaw('You are not authorized to access this page.');
    $this->assertNoRaw('Please login to access <b>Test: Webform: Access Denied</b>.');

    /**************************************************************************/
    // Page.
    /**************************************************************************/

    // Set access denied to display a dedicated page.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_PAGE);
    $webform->setSetting('form_access_denied_title', 'Webform: Access denied');
    $webform->setSetting('form_access_denied_attributes', ['style' => 'border: 1px solid red', 'class' => [], 'attributes' => []]);
    $webform->save();

    // Check custom access denied page.
    $this->drupalGet('/admin/structure/webform/manage/test_form_access_denied');
    $this->assertRaw('<h1 class="page-title">Webform: Access denied</h1>');
    $this->assertRaw('<div style="border: 1px solid red" class="webform-access-denied">Please login to access <b>Test: Webform: Access Denied</b>.</div>');

    /**************************************************************************/
    // Message via a block.
    /**************************************************************************/

    // Place block.
    $this->drupalPlaceBlock('webform_block', [
      'webform_id' => 'test_form_access_denied',
    ]);

    // Set access denied to default.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_DEFAULT);
    $webform->save();

    // Check block does not displays access denied message.
    $this->drupalGet('/<front>');
    $this->assertNoRaw('<div style="border: 1px solid red" class="webform-access-denied">Please login to access <b>Test: Webform: Access Denied</b>.</div>');

    // Set access denied to display a message.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_MESSAGE);
    $webform->save();

    // Check block displays access denied message.
    $this->drupalGet('/<front>');
    $this->assertRaw('<div style="border: 1px solid red" class="webform-access-denied">Please login to access <b>Test: Webform: Access Denied</b>.</div>');

    // Login.
    $this->drupalLogin($this->rootUser);

    // Check block displays wth webform.
    $this->drupalGet('/<front>');
    $this->assertNoRaw('<div class="webform-access-denied">Please login to access <b>Test: Webform: Access Denied</b>.</div>');
    $this->assertRaw('id="webform-submission-test-form-access-denied-user-1-add-form"');
  }

  /**
   * Tests webform submission access denied setting.
   */
  public function testWebformSubmissionAccessDenied() {
    // Create a webform submission.
    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('test_form_access_denied');
    $sid = $this->postSubmission($webform);
    $this->drupalLogout();

    /**************************************************************************/
    // Redirect.
    /**************************************************************************/

    // Check submission message is displayed.
    $this->drupalGet("admin/structure/webform/manage/test_form_access_denied/submission/$sid");
    $this->assertRaw("Please login to access <b>Test: Webform: Access Denied: Submission #$sid</b>.");

    /**************************************************************************/
    // Default.
    /**************************************************************************/

    // Set default access denied page.
    $webform->setSetting('submission_access_denied', WebformInterface::ACCESS_DENIED_DEFAULT);
    $webform->save();

    // Check default access denied page.
    $this->drupalGet("admin/structure/webform/manage/test_form_access_denied/submission/$sid");
    $this->assertRaw('You are not authorized to access this page.');
    $this->assertNoRaw("Please login to access <b>Test: Webform: Access Denied: Submission #$sid</b>.");

    /**************************************************************************/
    // Page.
    /**************************************************************************/

    // Set access denied to display a dedicated page.
    $webform->setSetting('submission_access_denied', WebformInterface::ACCESS_DENIED_PAGE);
    $webform->setSetting('submission_access_denied_title', 'Webform submission: Access denied');
    $webform->setSetting('submission_access_denied_attributes', ['style' => 'border: 1px solid red', 'class' => [], 'attributes' => []]);
    $webform->save();

    // Check custom access denied page.
    $this->drupalGet("admin/structure/webform/manage/test_form_access_denied/submission/$sid");
    $this->assertNoRaw('You are not authorized to access this page.');
    $this->assertRaw('<h1 class="page-title">Webform submission: Access denied</h1>');
    $this->assertRaw('<div style="border: 1px solid red" class="webform-submission-access-denied">Please login to access <b>Test: Webform: Access Denied: Submission #' . $sid . '</b>.</div>');
  }

}
