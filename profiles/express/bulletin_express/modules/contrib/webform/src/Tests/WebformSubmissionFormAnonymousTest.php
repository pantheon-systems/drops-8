<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission form anonymous settings.
 *
 * @group Webform
 */
class WebformSubmissionFormAnonymousTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_confidential'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();

    $this->addWebformSubmissionOwnPermissionsToAnonymous();
  }

  /**
   * Tests webform setting including confirmation.
   */
  public function testAnonymous() {

    /**************************************************************************/
    /* Test confidential submissions (form_confidential)*/
    /**************************************************************************/

    // Check logout warning.
    $webform_confidential = Webform::load('test_form_confidential');
    $this->drupalLogin($this->adminWebformUser);
    $this->drupalGet('webform/test_form_confidential');
    $this->assertNoFieldById('edit-name');
    $this->assertRaw('This form is confidential.');

    // Check anonymous access to webform.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_confidential');
    $this->assertFieldById('edit-name');
    $this->assertNoRaw('This form is confidential.');

    // Check that submission does not track the requests IP address.
    $sid = $this->postSubmission($webform_confidential, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getRemoteAddr(), t('(unknown)'));
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    // Check that previous submissions are visible.
    $this->drupalGet('webform/test_form_confidential');
    $this->assertRaw('View your previous submission');

    // Check that anoymous submissison is not converted to authenticated.
    // @see \Drupal\webform\WebformSubmissionStorage::userLogin
    $this->drupalLogin($this->adminWebformUser);
    $webform_submission = $this->loadSubmission($sid);
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    // Check that previous submissions $_SESSION was unset after login/logout.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_confidential');
    $this->assertNoRaw('View your previous submission.');
  }

}
