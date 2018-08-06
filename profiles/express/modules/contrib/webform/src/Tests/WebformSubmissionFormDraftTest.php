<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission form draft.
 *
 * @group Webform
 */
class WebformSubmissionFormDraftTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_draft_authenticated', 'test_form_draft_anonymous', 'test_form_draft_multiple', 'test_form_preview'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test webform submission form draft.
   */
  public function testDraft() {

    /**************************************************************************/
    // Autosave for anonymous draft to authenticated draft.
    /**************************************************************************/

    $webform_ids = [
      'test_form_draft_authenticated' => 'Test: Webform: Draft authenticated',
      'test_form_draft_anonymous' => 'Test: Webform: Draft anonymous',
    ];
    foreach ($webform_ids as $webform_id => $webform_title) {
      $is_authenticated = ($webform_id == 'test_form_draft_authenticated') ? TRUE : FALSE;

      // Login draft account.
      ($is_authenticated) ? $this->drupalLogin($this->normalUser) : $this->drupalLogout();

      $webform = Webform::load($webform_id);

      // Save a draft.
      $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = WebformSubmission::load($sid);

      // Check saved draft message.
      $this->assertRaw('Your draft has been saved');
      $this->assertNoRaw('You have an existing draft');

      // Check loaded draft message.
      $this->drupalGet("webform/$webform_id");
      $this->assertNoRaw('Your draft has been saved');
      $this->assertRaw('You have an existing draft');
      $this->assertFieldByName('name', 'John Smith');

      // Login admin account.
      $this->drupalLogin($this->adminSubmissionUser);

      // Check submission.
      $this->drupalGet("admin/structure/webform/manage/$webform_id/submission/$sid");
      $this->assertRaw('<div><b>Is draft:</b> Yes</div>');

      // Login draft account.
      ($is_authenticated) ? $this->drupalLogin($this->normalUser) : $this->drupalLogout();

      // Check update draft and bypass validation.
      $this->drupalPostForm("webform/$webform_id", [
        'name' => '',
        'comment' => 'Hello World!',
      ], t('Save Draft'));
      $this->assertRaw('Your draft has been saved');
      $this->assertNoRaw('You have an existing draft');
      $this->assertFieldByName('name', '');
      $this->assertFieldByName('comment', 'Hello World!');

      // Check preview of draft with valid data.
      $this->drupalPostForm("webform/$webform_id", [
        'name' => 'John Smith',
        'comment' => 'Hello World!',
      ], t('Preview'));
      $this->assertNoRaw('Your draft has been saved');
      $this->assertNoRaw('You have an existing draft');
      $this->assertNoFieldByName('name', '');
      $this->assertNoFieldByName('comment', 'Hello World!');
      $this->assertRaw('<label>Name</label>');
      $this->assertRaw('<label>Comment</label>');
      $this->assertRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');

      // Check submit.
      $this->drupalPostForm("webform/$webform_id", [], t('Submit'));
      $this->assertRaw("New submission added to $webform_title.");

      // Check submission not in draft.
      $this->drupalGet("webform/$webform_id");
      $this->assertNoRaw('Your draft has been saved');
      $this->assertNoRaw('You have an existing draft');
      $this->assertFieldByName('name', '');
      $this->assertFieldByName('comment', '');
    }

    /**************************************************************************/
    // Convert anonymous draft to authenticated draft.
    /**************************************************************************/

    $webform = Webform::load('test_form_draft_anonymous');

    // Save a draft.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $this->assertRaw('Your draft has been saved');

    // Check that submission is owned anonymous.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    // Check loaded draft message.
    $this->drupalGet('webform/test_form_draft_anonymous');
    $this->assertRaw('You have an existing draft');
    $this->assertFieldByName('name', 'John Smith');

    // Login the normal user.
    $this->drupalLogin($this->normalUser);

    // Check that submission is now owned by the normal user.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getOwnerId(), $this->normalUser->id());

    // Check that drafts are not convert when form_convert_anonymous = FALSE.
    $this->drupalLogout();
    $webform->setSetting('form_convert_anonymous', FALSE)->save();

    $sid = $this->postSubmission($webform, ['name' => 'John Smith']);
    $this->drupalLogin($this->normalUser);

    // Check that submission is still owned by anonymous user.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    // Logout.
    $this->drupalLogout();

    // Change 'test_form_draft_anonymous' to be confidential.
    $webform->setSetting('form_confidential', TRUE);

    // Save a draft.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $this->assertRaw('Your draft has been saved');

    // Check that submission is owned anonymous.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    // Check loaded draft message does NOT appear on confidential submissions.
    $this->drupalGet('webform/test_form_draft_anonymous');
    $this->assertRaw('You have an existing draft');

    // Login the normal user.
    $this->drupalLogin($this->normalUser);

    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    // Check that submission is NOT owned by the normal user.
    $this->assertNotEqual($webform_submission->getOwnerId(), $this->normalUser->id());

    // Check that submission is still anonymous.
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    /**************************************************************************/
    // Export.
    /**************************************************************************/

    $this->drupalLogin($this->adminSubmissionUser);

    // Check export with draft settings.
    $this->drupalGet('admin/structure/webform/manage/test_form_draft_authenticated/results/download');
    $this->assertFieldByName('state', 'all');

    // Check export without draft settings.
    $this->drupalGet('admin/structure/webform/manage/test_form_preview/results/download');
    $this->assertNoFieldByName('state', 'all');

    // Check autosave on submit with validation errors.
    $this->drupalPostForm('webform/test_form_draft_authenticated', [], t('Submit'));
    $this->assertRaw('Name field is required.');
    $this->drupalGet('webform/test_form_draft_authenticated');
    $this->assertRaw('You have an existing draft');

    // Check autosave on preview.
    $this->drupalPostForm('webform/test_form_draft_authenticated', ['name' => 'John Smith'], t('Preview'));
    $this->assertRaw('Please review your submission.');
    $this->drupalGet('webform/test_form_draft_authenticated');
    $this->assertRaw('You have an existing draft');
    $this->assertRaw('<label>Name</label>' . PHP_EOL . '        John Smith');
  }

  /**
   * Test webform draft multiple.
   */
  public function testDraftMultiple() {
    $this->drupalLogin($this->normalUser);

    $webform = Webform::load('test_form_draft_multiple');

    // Save first draft.
    $sid_1 = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $this->assertRaw('Submission saved. You may return to this form later and it will restore the current values.');
    $webform_submission_1 = WebformSubmission::load($sid_1);

    // Check restore first draft.
    $this->drupalGet('webform/test_form_draft_multiple');
    $this->assertNoRaw('You have saved drafts.');
    $this->assertRaw('You have a pending draft for this webform.');
    $this->assertFieldByName('name', '');

    // Check load pending draft using token.
    $this->drupalGet('webform/test_form_draft_multiple');
    $this->clickLink('Load your pending draft');
    $this->assertFieldByName('name', 'John Smith');
    $this->drupalGet('webform/test_form_draft_multiple', ['query' => ['token' => $webform_submission_1->getToken()]]);
    $this->assertFieldByName('name', 'John Smith');

    // Check user drafts.
    $this->drupalGet('webform/test_form_draft_multiple/drafts');
    $this->assertRaw('token=' . $webform_submission_1->getToken());

    // Save second draft.
    $sid_2 = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $webform_submission_2 = WebformSubmission::load($sid_2);
    $this->assertRaw('Submission saved. You may return to this form later and it will restore the current values.');
    $this->drupalGet('webform/test_form_draft_multiple');
    $this->assertNoRaw('You have a pending draft for this webform.');
    $this->assertRaw('You have pending drafts for this webform. <a href="' . base_path() . 'webform/test_form_draft_multiple/drafts">View your pending drafts</a>.');

    // Check user drafts now has second draft.
    $this->drupalGet('webform/test_form_draft_multiple/drafts');
    $this->assertRaw('token=' . $webform_submission_1->getToken());
    $this->assertRaw('token=' . $webform_submission_2->getToken());

    // Check that anonymous user can't load drafts.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_draft_multiple', ['query' => ['token' => $webform_submission_1->getToken()]]);
    $this->assertFieldByName('name', '');

    // Save third anonymous draft.
    $this->postSubmission($webform, ['name' => 'Jane Doe'], t('Save Draft'));
    $this->assertRaw('Submission saved. You may return to this form later and it will restore the current values.');

    // Check restore third anonymous draft.
    $this->drupalGet('webform/test_form_draft_multiple');
    $this->assertNoRaw('You have saved drafts.');
    $this->assertRaw('You have a pending draft for this webform.');
    $this->assertFieldByName('name', '');

    $this->drupalGet('webform/test_form_draft_multiple');
    $this->clickLink('Load your pending draft');
    $this->assertFieldByName('name', 'Jane Doe');
  }

}
