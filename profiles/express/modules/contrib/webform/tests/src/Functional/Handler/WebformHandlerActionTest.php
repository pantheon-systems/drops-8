<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for action webform handler functionality.
 *
 * @group Webform
 */
class WebformHandlerActionTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_action'];

  /**
   * Test action handler.
   */
  public function testActionHandler() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_action');

    // Create submission.
    $sid = $this->postSubmission($webform);

    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is not flagged (sticky).
    $this->assertFalse($webform_submission->isSticky());

    // Check that submission is unlocked.
    $this->assertFalse($webform_submission->isLocked());

    // Check that submission notes is empty.
    $this->assertTrue(empty($webform_submission->getNotes()));

    // Check that last note is empty.
    $this->assertTrue(empty($webform_submission->getElementData('notes_add')));

    // Flag and add new note to the submission.
    $edit = [
      'sticky' => 'flag',
      'notes_add' => 'This is the first note',
    ];
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_action/submission/$sid/edit", $edit, t('Save'));

    // Check messages.
    $this->assertRaw('Submission has been flagged.');
    $this->assertRaw('Submission notes have been updated.');

    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);

    // Check that sticky is set.
    $this->assertTrue($webform_submission->isSticky());

    // Change that notes_add is empty.
    $this->assertTrue(empty($webform_submission->getElementData('notes_add')));

    // Check that notes_last is updated.
    $this->assertEqual($webform_submission->getElementData('notes_last'), 'This is the first note');

    // Unflag and add new note to the submission.
    $edit = [
      'sticky' => 'unflag',
      'notes_add' => 'This is the second note',
    ];
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_action/submission/$sid/edit", $edit, t('Save'));

    // Check messages.
    $this->assertRaw('Submission has been unflagged.');
    // $this->assertRaw('Submission has been unlocked.');
    $this->assertRaw('Submission notes have been updated.');

    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);

    // Check that sticky is unset.
    $this->assertFalse($webform_submission->isSticky());

    // Change that notes_add is empty.
    $this->assertTrue(empty($webform_submission->getElementData('notes_add')));

    // Check that notes updated.
    $this->assertEqual($webform_submission->getNotes(), 'This is the first note' . PHP_EOL . PHP_EOL . 'This is the second note');

    // Check that notes_last is updated with second note.
    $this->assertEqual($webform_submission->getElementData('notes_last'), 'This is the second note');

    // Lock submission.
    $edit = [
      'lock' => 'locked',
    ];
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_action/submission/$sid/edit", $edit, t('Save'));

    // Check locked message.
    $this->assertRaw('Submission has been locked.');

    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is locked.
    $this->assertTrue($webform_submission->isLocked());
    $this->assertEqual(WebformSubmissionInterface::STATE_LOCKED, $webform_submission->getState());

    // Check that submission is locked.
    $this->drupalGet("admin/structure/webform/manage/test_handler_action/submission/$sid/edit");
    $this->assertRaw('This is submission was automatically locked.');

    // Programmatically unlock the submission.
    $webform_submission->setElementData('lock', 'unlocked');
    $webform_submission->save();

    $this->assertFalse($webform_submission->isLocked());
    $this->assertNotEqual(WebformSubmissionInterface::STATE_LOCKED, $webform_submission->getState());
  }

}
