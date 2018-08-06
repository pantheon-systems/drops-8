<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform storage tests.
 *
 * @group Webform
 */
class WebformSubmissionLogTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submission_log'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test webform submission log.
   */
  public function testSubmissionLog() {
    global $base_path;

    $webform = Webform::load('test_submission_log');

    // Check submission created.
    $sid_1 = $this->postSubmission($webform);
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 1);
    $this->assertEqual($log->sid, 1);
    $this->assertEqual($log->uid, 0);
    $this->assertEqual($log->handler_id, '');
    $this->assertEqual($log->operation, 'submission created');
    $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #1 created.');
    $this->assertEqual($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission draft created.
    $sid_2 = $this->postSubmission($webform, ['value' => 'Test'], t('Save Draft'));
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 2);
    $this->assertEqual($log->sid, 2);
    $this->assertEqual($log->uid, 0);
    $this->assertEqual($log->handler_id, '');
    $this->assertEqual($log->operation, 'draft created');
    $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #2 draft created.');
    $this->assertEqual($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission draft updated.
    $this->postSubmission($webform, ['value' => 'Test'], t('Save Draft'));
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 3);
    $this->assertEqual($log->sid, 2);
    $this->assertEqual($log->uid, 0);
    $this->assertEqual($log->handler_id, '');
    $this->assertEqual($log->operation, 'draft updated');
    $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #2 draft updated.');
    $this->assertEqual($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission completed.
    $this->postSubmission($webform);
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 4);
    $this->assertEqual($log->sid, 2);
    $this->assertEqual($log->uid, 0);
    $this->assertEqual($log->handler_id, '');
    $this->assertEqual($log->operation, 'submission completed');
    $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #2 completed using saved draft.');
    $this->assertEqual($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Login admin user.
    $this->drupalLogin($this->adminWebformUser);

    $submission_log = $this->getSubmissionLog();

    // Check submission #2 converted.
    $log = $submission_log[0];
    $this->assertEqual($log->lid, 6);
    $this->assertEqual($log->uid, $this->adminWebformUser->id());
    $this->assertEqual($log->sid, 2);
    $this->assertEqual($log->operation, 'submission converted');
    $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #2 converted from anonymous to ' . $this->adminWebformUser->label() . '.');

    // Check submission #1 converted.
    $log = $submission_log[1];
    $this->assertEqual($log->lid, 5);
    $this->assertEqual($log->uid, $this->adminWebformUser->id());
    $this->assertEqual($log->sid, 1);
    $this->assertEqual($log->operation, 'submission converted');
    $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #1 converted from anonymous to ' . $this->adminWebformUser->label() . '.');

    // Check submission updated.
    $this->drupalPostForm("admin/structure/webform/manage/test_submission_log/submission/$sid_2/edit", [], t('Save'));
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 7);
    $this->assertEqual($log->sid, 2);
    $this->assertEqual($log->uid, $this->adminWebformUser->id());
    $this->assertEqual($log->handler_id, '');
    /**************************************************************************/
    // $this->assertEqual($log->operation, 'submission completed');
    // $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #2 completed using saved draft.');
    /**************************************************************************/
    $this->assertEqual($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission deleted removes all log entries for this sid.
    $this->drupalPostForm("admin/structure/webform/manage/test_submission_log/submission/$sid_1/delete", [], t('Delete'));
    $this->drupalPostForm("admin/structure/webform/manage/test_submission_log/submission/$sid_2/delete", [], t('Delete'));
    $log = $this->getLastSubmissionLog();
    $this->assertFalse($log);

    // Check all results log table is empty.
    $this->drupalGet('admin/structure/webform/submissions/log');
    $this->assertRaw('No log messages available.');

    // Check webform results log table is empty.
    $this->drupalGet('admin/structure/webform/manage/test_submission_log/results/log');
    $this->assertRaw('No log messages available.');

    $sid_3 = $this->postSubmission($webform);
    WebformSubmission::load($sid_3);

    // Check all results log table has record.
    $this->drupalGet('admin/structure/webform/submissions/log');
    $this->assertNoRaw('No log messages available.');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/results/log">Test: Submission: Logging</a>');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/submission/3/log">3</a></td>');
    $this->assertRaw('Test: Submission: Logging: Submission #3 created.');

    // Check webform results log table has record.
    $this->drupalGet('admin/structure/webform/manage/test_submission_log/results/log');
    $this->assertNoRaw('No log messages available.');
    $this->assertNoRaw('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/results/log">Test: Submission: Logging</a>');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/submission/3/log">3</a></td>');
    $this->assertRaw('Test: Submission: Logging: Submission #3 created.');
  }

}
