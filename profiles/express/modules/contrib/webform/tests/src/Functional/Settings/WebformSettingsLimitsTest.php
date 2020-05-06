<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form limits.
 *
 * @group Webform
 */
class WebformSettingsLimitsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'block'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_limit', 'test_form_limit_wait'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Place webform test blocks.
    $this->placeWebformBlocks('webform_test_block_submission_limit');
  }

  /**
   * Tests webform submission form limits.
   */
  public function testFormLimits() {
    $own_submission_user = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    $webform_limit = Webform::load('test_form_limit');

    /**************************************************************************/

    $this->drupalGet('/webform/test_form_limit');

    // Check webform available.
    $this->assertFieldByName('op', 'Submit');

    // Check submission limit blocks.
    $this->assertRaw('0 user submission(s)');
    $this->assertRaw('1 user limit (every minute)');
    $this->assertRaw('0 webform submission(s)');
    $this->assertRaw('4 webform limit (every minute)');

    // Check submission limit tokens.
    $this->assertRaw('limit:webform: 4');
    $this->assertRaw('remaining:webform: 4');
    $this->assertRaw('limit:user: 1');
    $this->assertRaw('remaining:user: 1');

    $this->drupalLogin($own_submission_user);

    // Check that draft does not count toward limit.
    $this->postSubmission($webform_limit, [], t('Save Draft'));
    $this->drupalGet('/webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('A partially-completed form was found. Please complete the remaining portions.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks do not count draft.
    $this->assertRaw('0 user submission(s)');
    $this->assertRaw('0 webform submission(s)');

    // Check limit reached and webform not available for authenticated user.
    $sid = $this->postSubmission($webform_limit);
    $this->drupalGet('/webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks do count submission.
    $this->assertRaw('1 user submission(s)');
    $this->assertRaw('1 webform submission(s)');

    // Check authenticated user can edit own submission.
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');
    $this->assertFieldByName('op', 'Save');

    $this->drupalLogout();

    // Check admin post submission.
    $this->drupalLogin($this->rootUser);
    $sid = $this->postSubmission($webform_limit);
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $this->assertFieldByName('op', 'Save');
    $this->assertNoRaw('No more submissions are permitted.');

    // Check submission limit tokens do count submission.
    $this->assertRaw('remaining:webform: 2');
    $this->assertRaw('remaining:user: 0');

    // Check submission limit blocks.
    $this->assertRaw('1 user submission(s)');
    $this->assertRaw('2 webform submission(s)');

    $this->drupalLogout();

    // Allow anonymous users to edit own submission.
    $role = Role::load('anonymous');
    $role->grantPermission('edit own webform submission');
    $role->save();

    // Check webform is still available for anonymous users.
    $this->drupalGet('/webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Add 1 more submissions as an anonymous user making the total number of
    // submissions equal to 3.
    $sid = $this->postSubmission($webform_limit);

    // Check submission limit blocks.
    $this->assertRaw('1 user submission(s)');
    $this->assertRaw('3 webform submission(s)');

    // Check limit reached and webform not available for anonymous user.
    $this->drupalGet('/webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');

    // Check authenticated user can edit own submission.
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');
    $this->assertFieldByName('op', 'Save');

    // Add 1 more submissions as an root user making the total number of
    // submissions equal to 4.
    $this->drupalLogin($this->rootUser);
    $this->postSubmission($webform_limit);
    $this->drupalLogout();

    // Check total limit.
    $this->drupalGet('/webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('Only 4 submissions are allowed.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks.
    $this->assertRaw('0 user submission(s)');
    $this->assertRaw('4 webform submission(s)');

    // Check admin can still post submissions.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('Only 4 submissions are allowed.');
    $this->assertRaw('Only submission administrators are allowed to access this webform and create new submissions.');

    // Check submission limit blocks.
    $this->assertRaw('2 user submission(s)');
    $this->assertRaw('4 webform submission(s)');

    // Change submission completed to 1 hour ago.
    \Drupal::database()->query('UPDATE {webform_submission} SET completed = :completed', [':completed' => strtotime('-1 minute')]);

    // Check submission limit blocks are removed because the submission
    // intervals have passed.
    $this->drupalGet('/webform/test_form_limit');
    $this->assertRaw('0 user submission(s)');
    $this->assertRaw('0 webform submission(s)');

    /**************************************************************************/
    // Wait.
    /**************************************************************************/

    $webform_limit_wait = Webform::load('test_form_limit_wait');

    $this->postSubmission($webform_limit_wait);

    $this->drupalGet('/webform/test_form_limit_wait');
    $this->assertPattern('/webform_submission:interval:user:wait =&gt; \d+ seconds/');
  }

}
