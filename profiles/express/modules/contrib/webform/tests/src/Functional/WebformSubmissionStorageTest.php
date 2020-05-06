<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform storage tests.
 *
 * @group Webform
 */
class WebformSubmissionStorageTest extends WebformBrowserTestBase {

  /**
   * Test webform submission storage.
   */
  public function testSubmissionStorage() {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    // Create new webform.
    $id = $this->randomMachineName(8);
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => $id,
      'title' => $id,
      'elements' => Yaml::encode(['test' => ['#markup' => 'test']]),
    ]);
    $webform->save();

    // Create 3 submissions for user1.
    $user1 = $this->drupalCreateUser(['view own webform submission']);
    $this->drupalLogin($user1);
    $user1_submissions = [
      WebformSubmission::load($this->postSubmission($webform)),
      WebformSubmission::load($this->postSubmission($webform)),
      WebformSubmission::load($this->postSubmission($webform)),
    ];

    // Create 3 submissions for user2.
    $user2 = $this->drupalCreateUser();
    $this->drupalLogin($user2);
    $user2_submissions = [
      WebformSubmission::load($this->postSubmission($webform)),
      WebformSubmission::load($this->postSubmission($webform)),
      WebformSubmission::load($this->postSubmission($webform)),
    ];

    // Create admin user who can see all submissions.
    $admin_user = $this->drupalCreateUser(['administer webform']);

    // Check total.
    $this->assertEqual($storage->getTotal($webform), 6);
    $this->assertEqual($storage->getTotal($webform, NULL, $user1), 3);
    $this->assertEqual($storage->getTotal($webform, NULL, $user2), 3);

    // Check next submission.
    $this->drupalLogin($user1);
    $this->assertEqual($storage->getNextSubmission($user1_submissions[0], NULL, $user1)->id(), $user1_submissions[1]->id(), "User 1 can navigate forward to user 1's next submission");
    $this->assertNull($storage->getNextSubmission($user1_submissions[2], NULL, $user1), "User 1 can't navigate forward to user 2's next submission");
    $this->drupalLogin($user2);
    $this->assertNull($storage->getNextSubmission($user2_submissions[0], NULL, $user2), "User 2 can't navigate forward to user 2's next submission because of missing 'view own webform submission' permission");
    $this->drupalLogin($admin_user);
    $this->assertEqual($storage->getNextSubmission($user1_submissions[2], NULL)->id(), $user2_submissions[0]->id(), "Admin user can navigate between user submissions");
    $this->drupalLogout();

    // Check previous submission.
    $this->drupalLogin($user1);
    $this->assertEqual($storage->getPreviousSubmission($user1_submissions[1], NULL, $user1)->id(), $user1_submissions[0]->id(), "User 1 can navigate backward to user 1's previous submission");
    $this->drupalLogin($user2);
    $this->assertNull($storage->getPreviousSubmission($user2_submissions[0], NULL, $user2), "User 2 can't navigate backward to user 1's previous submission");
    $this->drupalLogin($admin_user);
    $this->assertEqual($storage->getPreviousSubmission($user2_submissions[0], NULL)->id(), $user1_submissions[2]->id(), "Admin user can navigate between user submissions");
    $this->drupalLogout();

    // Enable the saving of drafts.
    $webform->setSetting('draft', WebformInterface::DRAFT_AUTHENTICATED)->save();

    // Create drafts for user1 and user2.
    $this->drupalLogin($user1);
    $this->postSubmission($webform, [], t('Save Draft'));
    $this->drupalLogin($user2);
    $this->postSubmission($webform, [], t('Save Draft'));

    // Check totals remains the same with drafts.
    $this->assertEqual($storage->getTotal($webform), 6);
    $this->assertEqual($storage->getTotal($webform, NULL, $user1), 3);
    $this->assertEqual($storage->getTotal($webform, NULL, $user2), 3);

    // Save current drafts for user1 and user2.
    $this->drupalLogin($user1);
    $this->postSubmission($webform);
    $this->drupalLogin($user2);
    $this->postSubmission($webform);

    // Check totals have been updated.
    $this->assertEqual($storage->getTotal($webform), 8);
    $this->assertEqual($storage->getTotal($webform, NULL, $user1), 4);
    $this->assertEqual($storage->getTotal($webform, NULL, $user2), 4);
  }

}
