<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results disabled.
 *
 * @group Webform
 */
class WebformResultsDisabledTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_results_disabled'];

  /**
   * Tests webform setting including confirmation.
   */
  public function testSettings() {
    $this->drupalLogin($this->rootUser);

    // Check results disabled.
    $webform_results_disabled = Webform::load('test_form_results_disabled');
    $webform_submission = $this->postSubmission($webform_results_disabled);
    $this->assertFalse($webform_submission, 'Submission not saved to the database.');

    // Check that error message is displayed and form is available for admins.
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw(t('Unable to display this webform. Please contact the site administrator.'));

    // Check that error message not displayed and form is disabled for everyone.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertNoRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw(t('Unable to display this webform. Please contact the site administrator.'));

    // Enabled ignore disabled results.
    $webform_results_disabled->setSetting('results_disabled_ignore', TRUE);
    $webform_results_disabled->save();
    $this->drupalLogin($this->rootUser);

    // Check that no error message is displayed and form is available for admins.
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertNoRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertNoRaw(t('Unable to display this webform. Please contact the site administrator.'));
    $this->assertFieldByName('op', 'Submit');

    // Check that results tab is not accessible.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertResponse(403);

    // Check that error message not displayed and form is enabled for everyone.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertNoRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertNoRaw(t('Unable to display this webform. Please contact the site administrator.'));
    $this->assertFieldByName('op', 'Submit');

    // Unset disabled results.
    $webform_results_disabled->setSetting('results_disabled', FALSE);
    $webform_results_disabled->save();

    // Login admin.
    $this->drupalLogin($this->rootUser);

    // Check that results tab is accessible.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertResponse(200);

    // Post a submission.
    $sid = $this->postSubmissionTest($webform_results_disabled);
    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is available.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertNoRaw('This webform is currently not saving any submitted data');
    $this->assertRaw('>' . $webform_submission->serial() . '<');

    // Set disabled results.
    $webform_results_disabled->setSetting('results_disabled', TRUE);
    $webform_results_disabled->save();

    // Check that submission is still available with warning.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertRaw('This webform is currently not saving any submitted data');
    $this->assertRaw('>' . $webform_submission->serial() . '<');

    // Delete the submission.
    $webform_submission->delete();

    // Check that results tab is not accessible.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertResponse(403);
  }

}
