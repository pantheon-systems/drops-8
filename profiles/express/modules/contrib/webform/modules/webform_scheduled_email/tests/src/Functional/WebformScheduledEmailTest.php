<?php

namespace Drupal\Tests\webform_scheduled_email\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform scheduled email handler.
 *
 * @group WebformScheduledEmail
 */
class WebformScheduledEmailTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_scheduled_email', 'webform_scheduled_email_test', 'webform_node'];

  /**
   * Tests webform schedule email handler.
   */
  public function testWebformScheduledEmail() {
    $webform_schedule = Webform::load('test_handler_scheduled_email');

    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $scheduled_manager */
    $scheduled_manager = \Drupal::service('webform_scheduled_email.manager');

    $yesterday = date($scheduled_manager->getDateFormat(), strtotime('-1 days'));
    $tomorrow = date($scheduled_manager->getDateFormat(), strtotime('+1 days'));

    /**************************************************************************/
    // Submission scheduling.
    /**************************************************************************/

    // Check scheduled email yesterday.
    $sid = $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email scheduled by Yesterday handler to be sent on $yesterday.");

    // Check scheduled email yesterday database send date.
    $scheduled_email = $scheduled_manager->load($webform_submission, 'yesterday');
    $this->assertEqual($scheduled_email->send, strtotime($yesterday));
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_SEND);

    // Check send yesterday email.
    $scheduled_manager->cron();
    $scheduled_email = $scheduled_manager->load($webform_submission, 'yesterday');
    $this->assertFalse($scheduled_email);

    // Check schedule other +14 days.
    $sid = $this->postSubmission($webform_schedule, ['send' => 'other', 'date[date]' => '2001-01-01'], 'Save Draft');
    $webform_submission = WebformSubmission::load($sid);
    $scheduled_email = $scheduled_manager->load($webform_submission, 'other');
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email scheduled by Other handler to be sent on 2001-01-15.");
    $this->assertEqual($scheduled_email->send, strtotime('2001-01-15'));
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_SEND);

    // Check reschedule other +14 days.
    $this->postSubmission($webform_schedule, ['send' => 'other', 'date[date]' => '2002-02-02'], 'Save Draft');
    $scheduled_email = $scheduled_manager->load($webform_submission, 'other');
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email rescheduled by Other handler to be sent on 2002-02-16.");
    $this->assertEqual($scheduled_email->send, strtotime('2002-02-16'));
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_SEND);

    // Check saving webform submission reschedules.
    $webform_submission->save();
    $scheduled_email = $scheduled_manager->load($webform_submission, 'other');
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_SEND);

    // Delete webform submission which deletes the scheduled email record.
    $webform_submission->delete();

    // Check delete removed scheduled email.
    $this->assertEqual($scheduled_manager->total(), 0);

    // Check schedule email for draft.
    $draft_reminder = date($scheduled_manager->getDateFormat(), strtotime('+14 days'));
    $sid = $this->postSubmission($webform_schedule, ['send' => 'draft_reminder'], 'Save Draft');
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email scheduled by Draft reminder handler to be sent on $draft_reminder.");
    $this->assertEqual($scheduled_manager->total(), 1);

    // Check unschedule email for draft.
    $this->postSubmission($webform_schedule, []);
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email unscheduled for Draft reminder handler.");
    $this->assertEqual($scheduled_manager->total(), 0);

    // Check broken/invalid date.
    $sid = $this->postSubmission($webform_schedule, ['send' => 'broken']);
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email not scheduled for Broken handler because [broken] is not a valid date/token.");
    $this->assertEqual($scheduled_manager->total($webform_schedule), 0);

    /**************************************************************************/
    // Submission scheduling with date/time.
    /**************************************************************************/

    // Change schedule type to 'datetime'.
    \Drupal::configFactory()->getEditable('webform_scheduled_email.settings')
      ->set('schedule_type', 'datetime')
      ->save();

    // Check other +14 days with time.
    $sid = $this->postSubmission($webform_schedule, ['send' => 'other', 'date[date]' => '2001-01-01', 'date[time]' => '02:00:00'], 'Save Draft');
    $webform_submission = WebformSubmission::load($sid);
    $scheduled_email = $scheduled_manager->load($webform_submission, 'other');
    $this->assertText("Test: Handler: Test scheduled email: Submission #$sid: Email scheduled by Other handler to be sent on 2001-01-15 02:00:00.");
    $this->assertEqual($scheduled_email->send, strtotime('2001-01-15 02:00:00'));
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_SEND);

    // Change schedule type back to 'date'.
    \Drupal::configFactory()->getEditable('webform_scheduled_email.settings')
      ->set('schedule_type', 'date')
      ->save();

    /**************************************************************************/
    // Check deleting handler removes scheduled emails.
    // @todo Figure out why the below exception is occurring during tests only.
    // "Drupal\Component\Plugin\Exception\PluginNotFoundException: Plugin ID 'tomorrow' was not found. "
    // $handler = $webform->getHandler('yesterday');
    // $webform->deleteWebformHandler($handler);
    // $total = \Drupal::database()->select('webform_scheduled_email')->countQuery()->execute()->fetchField();
    // $this->assertEqual($total, 3);
    /**************************************************************************/

    /**************************************************************************/
    // Webform scheduling.
    /**************************************************************************/

    // Purge all submissions.
    $this->purgeSubmissions();

    // Create 3 tomorrow scheduled emails.
    $this->postSubmission($webform_schedule, ['send' => 'tomorrow']);
    $this->postSubmission($webform_schedule, ['send' => 'tomorrow']);
    $this->postSubmission($webform_schedule, ['send' => 'tomorrow']);
    $this->assertEqual($scheduled_manager->total($webform_schedule), 3);

    // Create 3 yesterday scheduled emails.
    $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $this->assertEqual($scheduled_manager->total($webform_schedule), 6);

    // Send the 3 yesterday scheduled emails.
    $stats = $scheduled_manager->cron();
    $this->assertEqual($stats['sent'], 3);

    // Check on tomorrow scheduled emails remain.
    $this->assertEqual($scheduled_manager->total($webform_schedule), 3);

    // Reschedule yesterday submissions which includes all submissions.
    $scheduled_manager->schedule($webform_schedule, 'yesterday');
    $this->assertEqual($scheduled_manager->stats($webform_schedule), [
      'total' => 9,
      'waiting' => 6,
      'queued' => 3,
      'ready' => 0,
    ]);

    // Runs Reschedule yesterday submissions which includes all submissions.
    $stats = $scheduled_manager->cron();
    $this->assertNotEqual($stats['sent'], 6);$this->assertEqual($stats['sent'], 3);
    $this->assertEqual($scheduled_manager->stats($webform_schedule), [
      'total' => 3,
      'waiting' => 0,
      'queued' => 3,
      'ready' => 0,
    ]);

    // Reschedule tomorrow submissions.
    $scheduled_manager->schedule($webform_schedule, 'tomorrow');
    $this->assertEqual($scheduled_manager->total($webform_schedule), 6);
    $this->assertEqual($scheduled_manager->waiting($webform_schedule), 6);
    $this->assertEqual($scheduled_manager->ready($webform_schedule), 0);

    /**************************************************************************/
    // Webform scheduling with conditions.
    /**************************************************************************/

    // Purge all submissions.
    $this->purgeSubmissions();

    // Create 3 yesterday scheduled emails.
    $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $this->postSubmission($webform_schedule, ['send' => 'yesterday']);
    $this->assertEqual($scheduled_manager->total($webform_schedule), 3);
    $this->assertEqual($scheduled_manager->stats(), [
      'total' => 3,
      'waiting' => 0,
      'queued' => 0,
      'ready' => 3,
    ]);

    // Add condition to only send yesterday email if 'value' is filled.
    /** @var \Drupal\webform\Plugin\WebformHandlerInterface $yesterday_handler */
    $yesterday_handler = $webform_schedule->getHandler('yesterday');
    $conditions = ['enabled' => [':input[name="value"]' => ['filled' => TRUE]]];
    $yesterday_handler->setConditions($conditions);
    // NOTE: Executing $webform_schedule->save() throws the below
    // unexplainable error.
    //
    // TypeError: Argument 1 passed to
    // Drupal\webform\WebformSubmissionConditionsValidator::validateConditions()
    // must be of the type array, null given
    // $webform_schedule->save() ;
    //
    // Check that 3 yesterday scheduled emails are skipped and removed.
    $stats = $scheduled_manager->cron();
    $this->assertEqual($stats['skipped'], 3);
    $this->assertEqual($scheduled_manager->stats(), [
      'total' => 0,
      'waiting' => 0,
      'queued' => 0,
      'ready' => 0,
    ]);

    // Clear yesterday conditions.
    $yesterday_handler->setConditions([]);

    /**************************************************************************/
    // Ignore past scheduling.
    /**************************************************************************/

    // Purge all submissions.
    $this->purgeSubmissions();

    // Check last year email can't be scheduled.
    $sid = $this->postSubmission($webform_schedule, ['send' => 'last_year']);
    $this->assertEqual($scheduled_manager->total($webform_schedule), 0);
    $this->assertRaw('<em class="placeholder">Test: Handler: Test scheduled email: Submission #' . $sid . '</em>: Email <b>ignored</b> by <em class="placeholder">Last year</em> handler to be sent on <em class="placeholder">2016-01-01</em>.');

    /**************************************************************************/
    // Source entity scheduling.
    /**************************************************************************/

    // Purge all submissions.
    $this->purgeSubmissions();

    // Create webform node.
    $webform_node = $this->createWebformNode($webform_schedule->id());
    $sids = [
      $this->postNodeSubmission($webform_node, ['send' => 'tomorrow']),
      $this->postNodeSubmission($webform_node, ['send' => 'tomorrow']),
      $this->postNodeSubmission($webform_node, ['send' => 'tomorrow']),
    ];
    $this->assertEqual($scheduled_manager->total(), 3);
    // Get first submission.
    $sid = $sids[0];
    $webform_submission = WebformSubmission::load($sid);

    // Check first submission.
    $scheduled_email = $scheduled_manager->load($webform_submission, 'tomorrow');

    // Check queued and total.
    $this->assertEqual($scheduled_manager->stats(), [
      'total' => 3,
      'waiting' => 0,
      'queued' => 3,
      'ready' => 0,
    ]);
    $this->assertEqual($scheduled_manager->stats($webform_node), [
      'total' => 3,
      'waiting' => 0,
      'queued' => 3,
      'ready' => 0,
    ]);

    // Check first submission state is send.
    $this->assertEqual($scheduled_email->send, strtotime($tomorrow));
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_SEND);

    // Check updating node reschedules emails.
    $webform_node->save();

    // Check waiting and total.
    $this->assertEqual($scheduled_manager->stats(), [
      'total' => 3,
      'waiting' => 3,
      'queued' => 0,
      'ready' => 0,
    ]);
    $this->assertEqual($scheduled_manager->stats($webform_node), [
      'total' => 3,
      'waiting' => 3,
      'queued' => 0,
      'ready' => 0,
    ]);

    // Check first submission state is reschedule.
    $scheduled_email = $scheduled_manager->load($webform_submission, 'tomorrow');
    $this->assertEqual($scheduled_email->state, $scheduled_manager::SUBMISSION_RESCHEDULE);

    // Run cron to trigger scheduling.
    $scheduled_manager->cron();

    // Check queued and total.
    $this->assertEqual($scheduled_manager->stats(), [
      'total' => 3,
      'waiting' => 0,
      'queued' => 3,
      'ready' => 0,
    ]);
    $this->assertEqual($scheduled_manager->stats($webform_node), [
      'total' => 3,
      'waiting' => 0,
      'queued' => 3,
      'ready' => 0,
    ]);

    // Check deleting node unscheduled emails.
    $webform_node->delete();
    $this->assertEqual($scheduled_manager->stats(), [
      'total' => 3,
      'waiting' => 3,
      'queued' => 0,
      'ready' => 0,
    ]);

    // Run cron to trigger unscheduling.
    $scheduled_manager->cron();
    $this->assertEqual($scheduled_manager->total(), 0);

    // Purge all submissions.
    $this->purgeSubmissions();

    /**************************************************************************/
    // Testing.
    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    // Check 'Other' email will be sent immediately message when testing.
    $this->drupalGet('/webform/test_handler_scheduled_email/test');
    $this->assertRaw('The <em class="placeholder">Other</em> email will be sent immediately upon submission.');

    // Check 'Other' email is sent immediately via testing.
    $this->drupalPostForm('/webform/test_handler_scheduled_email/test', ['send' => 'other', 'date[date]' => '2101-01-01'], t('Submit'));
    $this->assertEqual($scheduled_manager->total(), 0);
    $this->assertRaw('Webform submission from: Test: Handler: Test scheduled email</em> sent to <em class="placeholder">simpletest@example.com</em> from <em class="placeholder">Drupal</em> [<em class="placeholder">simpletest@example.com</em>');
    $this->assertRaw('Debug: Email: Other');
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeSubmissions() {
    // Manually purge submissions to trigger deletion of scheduled emails.
    $webform_submissions = WebformSubmission::loadMultiple();
    foreach ($webform_submissions as $webform_submission) {
      $webform_submission->delete();
    }

    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $scheduled_manager */
    $scheduled_manager = \Drupal::service('webform_scheduled_email.manager');
    $this->assertEqual($scheduled_manager->total(), 0);
  }

}
