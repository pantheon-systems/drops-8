<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Component\Utility\Html;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form draft.
 *
 * @group Webform
 */
class WebformSettingsDraftTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_draft_authenticated', 'test_form_draft_anonymous', 'test_form_draft_multiple', 'test_form_preview'];

  /**
   * Test webform submission form draft.
   */
  public function testDraft() {
    $normal_user = $this->drupalCreateUser(['view own webform submission']);

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /**************************************************************************/
    // Draft access.
    /**************************************************************************/

    // Check access denied to review drafts when disabled.
    $this->drupalGet('/webform/contact/drafts');
    $this->assertResponse(403);

    // Check access denied to review authenticated drafts.
    $this->drupalGet('/webform/test_form_draft_authenticated/drafts');
    $this->assertResponse(403);

    // Check access allowed to review anonymous drafts.
    $this->drupalGet('/webform/test_form_draft_anonymous/drafts');
    $this->assertResponse(200);

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
      ($is_authenticated) ? $this->drupalLogin($normal_user) : $this->drupalLogout();

      $webform = Webform::load($webform_id);

      // Save a draft.
      $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = WebformSubmission::load($sid);

      // Check saved draft message.
      $this->assertRaw('Your draft has been saved');
      $this->assertNoRaw('You have an existing draft');

      // Check access allowed to review drafts.
      $this->drupalGet("webform/$webform_id/drafts");
      $this->assertResponse(200);

      // Check draft title and info.
      $account = ($is_authenticated) ? $normal_user : User::getAnonymousUser();
      $this->assertRaw('<title>' . Html::escape('Drafts for ' . $webform->label() . ' for ' . ($account->getAccountName() ?: 'Anonymous') . ' | Drupal') . '</title>');
      $this->assertRaw('<div>1 draft</div>');

      // Check loaded draft message.
      $this->drupalGet("webform/$webform_id");
      $this->assertNoRaw('Your draft has been saved');
      $this->assertRaw('You have an existing draft');
      $this->assertFieldByName('name', 'John Smith');

      // Check no draft message when webform is closed.
      $webform->setStatus(FALSE)->save();
      $this->drupalGet("webform/$webform_id");
      $this->assertNoRaw('You have an existing draft');
      $this->assertNoFieldByName('name', 'John Smith');
      $this->assertRaw('Sorry… This form is closed to new submissions.');
      $webform->setStatus(TRUE)->save();

      // Login admin account.
      $this->drupalLogin($admin_submission_user);

      // Check submission.
      $this->drupalGet("admin/structure/webform/manage/$webform_id/submission/$sid");
      $this->assertRaw('<div><b>Is draft:</b> Yes</div>');

      // Login draft account.
      ($is_authenticated) ? $this->drupalLogin($normal_user) : $this->drupalLogout();

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
    $this->drupalGet('/webform/test_form_draft_anonymous');
    $this->assertRaw('You have an existing draft');
    $this->assertFieldByName('name', 'John Smith');

    // Login the normal user.
    $this->drupalLogin($normal_user);

    // Check that submission is now owned by the normal user.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getOwnerId(), $normal_user->id());

    // Check that drafts are not convert when form_convert_anonymous = FALSE.
    $this->drupalLogout();
    $webform->setSetting('form_convert_anonymous', FALSE)->save();

    $sid = $this->postSubmission($webform, ['name' => 'John Smith']);
    $this->drupalLogin($normal_user);

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
    $this->drupalGet('/webform/test_form_draft_anonymous');
    $this->assertRaw('You have an existing draft');

    // Login the normal user.
    $this->drupalLogin($normal_user);

    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    // Check that submission is NOT owned by the normal user.
    $this->assertNotEqual($webform_submission->getOwnerId(), $normal_user->id());

    // Check that submission is still anonymous.
    $this->assertEqual($webform_submission->getOwnerId(), 0);

    /**************************************************************************/
    // Export.
    /**************************************************************************/

    $this->drupalLogin($admin_submission_user);

    // Check export with draft settings.
    $this->drupalGet('/admin/structure/webform/manage/test_form_draft_authenticated/results/download');
    $this->assertFieldByName('state', 'all');

    // Check export without draft settings.
    $this->drupalGet('/admin/structure/webform/manage/test_form_preview/results/download');
    $this->assertNoFieldByName('state', 'all');

    // Check autosave on submit with validation errors.
    $this->drupalPostForm('/webform/test_form_draft_authenticated', [], t('Submit'));
    $this->assertRaw('Name field is required.');
    $this->drupalGet('/webform/test_form_draft_authenticated');
    $this->assertRaw('You have an existing draft');

    // Check autosave on preview.
    $this->drupalPostForm('/webform/test_form_draft_authenticated', ['name' => 'John Smith'], t('Preview'));
    $this->assertRaw('Please review your submission.');
    $this->drupalGet('/webform/test_form_draft_authenticated');
    $this->assertRaw('You have an existing draft');
    $this->assertRaw('<label>Name</label>' . PHP_EOL . '        John Smith');

    /**************************************************************************/
    // Test webform draft multiple.
    /**************************************************************************/

    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $this->drupalLogin($normal_user);

    $webform = Webform::load('test_form_draft_multiple');

    // Save first draft.
    $sid_1 = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $this->assertRaw('Submission saved. You may return to this form later and it will restore the current values.');
    $webform_submission_1 = WebformSubmission::load($sid_1);

    // Check restore first draft.
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have saved drafts.');
    $this->assertRaw('You have a pending draft for this webform.');
    $this->assertFieldByName('name', '');

    // Check customizing default draft previous message.
    $default_draft_pending_single_message = $config->get('settings.default_draft_pending_single_message');
    $config->set('settings.default_draft_pending_single_message', '{default_draft_pending_single_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have a pending draft for this webform.');
    $this->assertRaw('{default_draft_pending_single_message}');
    $config->set('settings.default_draft_pending_single_message', $default_draft_pending_single_message)->save();

    // Check customizing draft previous message.
    $webform->setSetting('draft_pending_single_message', '{draft_pending_single_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have a pending draft for this webform.');
    $this->assertRaw('{draft_pending_single_message}');
    $webform->setSetting('draft_pending_single_message', '')->save();

    // Check load pending draft using token.
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->clickLink('Load your pending draft');
    $this->assertFieldByName('name', 'John Smith');
    $this->drupalGet('/webform/test_form_draft_multiple', ['query' => ['token' => $webform_submission_1->getToken()]]);
    $this->assertFieldByName('name', 'John Smith');

    // Check user drafts.
    $this->drupalGet('/webform/test_form_draft_multiple/drafts');
    $this->assertRaw('token=' . $webform_submission_1->getToken());

    // Save second draft.
    $sid_2 = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $webform_submission_2 = WebformSubmission::load($sid_2);
    $this->assertRaw('Submission saved. You may return to this form later and it will restore the current values.');
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have a pending draft for this webform.');
    $this->assertRaw('You have pending drafts for this webform. <a href="' . base_path() . 'webform/test_form_draft_multiple/drafts">View your pending drafts</a>.');

    // Check customizing default drafts previous message.
    $default_draft_pending_multiple_message = $config->get('settings.default_draft_pending_multiple_message');
    $config->set('settings.default_draft_pending_multiple_message', '{default_draft_pending_multiple_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have pending drafts for this webform.');
    $this->assertRaw('{default_draft_pending_multiple_message}');
    $config->set('settings.default_draft_pending_multiple_message', $default_draft_pending_multiple_message)->save();

    // Check customizing drafts previous message.
    $webform->setSetting('draft_pending_multiple_message', '{draft_pending_multiple_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have pending drafts for this webform.');
    $this->assertRaw('{draft_pending_multiple_message}');
    $webform->setSetting('draft_pending_multiple_message', '')->save();

    // Check user drafts now has second draft.
    $this->drupalGet('/webform/test_form_draft_multiple/drafts');
    $this->assertRaw('token=' . $webform_submission_1->getToken());
    $this->assertRaw('token=' . $webform_submission_2->getToken());

    // Check that anonymous user can't load drafts.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_form_draft_multiple', ['query' => ['token' => $webform_submission_1->getToken()]]);
    $this->assertFieldByName('name', '');

    // Save third anonymous draft.
    $this->postSubmission($webform, ['name' => 'Jane Doe'], t('Save Draft'));
    $this->assertRaw('Submission saved. You may return to this form later and it will restore the current values.');

    // Check restore third anonymous draft.
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->assertNoRaw('You have saved drafts.');
    $this->assertRaw('You have a pending draft for this webform.');
    $this->assertFieldByName('name', '');

    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->clickLink('Load your pending draft');
    $this->assertFieldByName('name', 'Jane Doe');

    /**************************************************************************/
    // Test webform submission form reset draft.
    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_draft_authenticated');

    // Check saved draft.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], t('Save Draft'));
    $this->assertNotNull($sid);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($sid, $webform_submission->id());

    // Check reset delete's the draft.
    $this->postSubmission($webform, [], t('Reset'));
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertNull($webform_submission);

    // Check submission with comment.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith', 'comment' => 'This is a comment'], t('Save Draft'));
    $this->postSubmission($webform);
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual('This is a comment', $webform_submission->getElementData('comment'));

    // Check submitted draft is not delete on reset.
    $this->drupalPostForm('/admin/structure/webform/manage/test_form_draft_authenticated/submission/' . $sid . '/edit', ['comment' => 'This is ignored'], t('Reset'));
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($sid, $webform_submission->id());
    $this->assertEqual('This is a comment', $webform_submission->getElementData('comment'));
    $this->assertNotEqual('This is ignored', $webform_submission->getElementData('comment'));
  }

}
