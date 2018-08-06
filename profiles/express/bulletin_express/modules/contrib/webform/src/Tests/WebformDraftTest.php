<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform draft.
 *
 * @group Webform
 */
class WebformDraftTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_draft_authenticated', 'test_form_draft_anonymous', 'test_form_preview'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test webform draft with autosave.
   */
  public function testDraftWithAutosave() {
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
      $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save a draft'));
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
      ], t('Save a draft'));
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
      $this->assertRaw('<b>Name</b><br/>');
      $this->assertRaw('<b>Comment</b><br/>');
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
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save a draft'));
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

    /**************************************************************************/
    // Export.
    /**************************************************************************/

    $this->drupalLogin($this->adminSubmissionUser);

    // Check export with draft settings.
    $this->drupalGet('admin/structure/webform/manage/test_form_draft_authenticated/results/download');
    $this->assertFieldByName('export[download][state]', 'all');

    // Check export without draft settings.
    $this->drupalGet('admin/structure/webform/manage/test_form_preview/results/download');
    $this->assertNoFieldByName('export[download][state]', 'all');

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
    $this->assertRaw('<b>Name</b><br/>John Smith<br/><br/>');
  }

}
