<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for updating webform submission using tokenized URL.
 *
 * @group Webform
 */
class WebformSubmissionTokenUpdateTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_token_update'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test updating webform submission using tokenized URL.
   */
  public function testTokenUpdateTest() {
    $webform = Webform::load('test_token_update');

    // Post test submission.
    $this->drupalLogin($this->rootUser);
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);

    // Check token update access allowed.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertRaw('Submission information');
    $this->assertFieldByName('textfield', $webform_submission->getElementData('textfield'));

    // Check token update access denied.
    $webform->setSetting('token_update', FALSE)->save();
    $this->drupalLogin($this->normalUser);
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
    $webform->setSetting('token_update', TRUE)->save();
    $webform->save();

    // Check that access is denied for anonymous user.
    $this->drupalGet('webform/test_token_update');
    $this->assertResponse(403);

    // Check token update access allowed for anonymous user.
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
  }

}
