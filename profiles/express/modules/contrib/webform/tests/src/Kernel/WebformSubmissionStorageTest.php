<?php

namespace Drupal\Tests\webform\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionStorageInterface;

/**
 * Tests webform submission storage.
 *
 * @group webform
 */
class WebformSubmissionStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'path', 'field', 'webform'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // @todo Remove once Drupal 8.8.x is only supported.
    if (floatval(\Drupal::VERSION) >= 8.8) {
      $this->installEntitySchema('path_alias');
    }
    $this->installSchema('webform', ['webform']);
    $this->installConfig('webform');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Test webform submission storage.
   */
  public function testStorage() {
    $webform = Webform::create([
      'id' => $this->randomMachineName(),
    ]);
    $webform->save();
    $webform_submission = WebformSubmission::create([
      'webform_id' => $webform->id(),
    ]);
    $webform_submission->save();

    // Check load by entities.
    $webform_submissions = \Drupal::entityTypeManager()->getStorage('webform_submission')->loadByEntities($webform);
    $this->assertEquals($webform_submission->id(), key($webform_submissions));
  }

  /**
   * Test purging of the webform submissions.
   *
   * @dataProvider providerPurge
   */
  public function testPurge($webform_purging, $webform_submissions_definition, $purged) {
    $request_time = \Drupal::time()->getRequestTime();
    $days_to_seconds = 60 * 60 * 24;
    $purge_days = 10;
    $purge_amount = 2;

    $webform = Webform::create([
      'id' => $this->randomMachineName(),
    ]);
    $webform->setSetting('purge', $webform_purging);
    $webform->setSetting('purge_days', $purge_days);
    $webform->save();

    $webform_no_purging = Webform::create([
      'id' => $this->randomMachineName(),
    ]);
    $webform_no_purging->setSetting('purge', WebformSubmissionStorageInterface::PURGE_NONE);
    $webform_no_purging->save();

    foreach ($webform_submissions_definition as $definition) {
      foreach ([$webform, $webform_no_purging] as $v) {
        $webform_submission = WebformSubmission::create([
          'webform_id' => $v->id(),
        ]);
        $webform_submission->in_draft = $definition[0];
        $webform_submission->setCreatedTime($definition[1] ? ($request_time - ($purge_days + 1) * $days_to_seconds) : $request_time);
        $webform_submission->save();
      }
    }

    \Drupal::entityTypeManager()->getStorage('webform_submission')->purge($purge_amount);

    // Make sure nothing has been purged in the webform where purging is
    // disabled.
    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('webform_id', $webform_no_purging->id());
    $result = $query->execute();
    $this->assertEquals(count($webform_submissions_definition), count($result), 'No purging is executed when webform is not set up to purge.');

    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('webform_id', $webform->id());
    $result = [];
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $result[$submission->serial()] = $submission;
    }
    foreach ($purged as $sequence_id) {
      $this->assertFalse(isset($result[$sequence_id]), 'Webform submission with sequence ' . $sequence_id . ' is purged.');
    }
    $this->assertEquals(count($webform_submissions_definition) - count($purged), count($result), 'Remaining webform submissions are not purged.');
  }

  /**
   * Data provider for testPurge().
   *
   * @see testPurge()
   */
  public function providerPurge() {
    // The structure of each test case data is the following:
    // 0: (string) The webform 'purge' setting
    // 1: (array) Array of webform submissions to create in the webforms. It
    //    should be an array with the following structure:
    //    0: (bool) Whether it is a draft
    //    1: (bool) Whether the submission should be created in such time when
    //       that it becomes eligible for purging based on its creation time
    // 2: (array) Array of webform submission sequence IDs that should be purged
    //    in the test.
    $tests = [];

    // Test that only drafts are purged.
    $tests[] = [
      WebformSubmissionStorageInterface::PURGE_DRAFT,
      [
        [TRUE, TRUE],
        [TRUE, FALSE],
        [FALSE, TRUE],
        [FALSE, FALSE],
      ],
      [1],
    ];

    // Test that only completed submissions are purged.
    $tests[] = [
      WebformSubmissionStorageInterface::PURGE_COMPLETED,
      [
        [TRUE, TRUE],
        [TRUE, FALSE],
        [FALSE, TRUE],
        [FALSE, FALSE],
      ],
      [3],
    ];

    // Test that both completed and draft submissions are purged.
    $tests[] = [
      WebformSubmissionStorageInterface::PURGE_ALL,
      [
        [TRUE, TRUE],
        [TRUE, FALSE],
        [FALSE, TRUE],
        [FALSE, FALSE],
      ],
      [1, 3],
    ];

    // Test the cron size parameter.
    $tests[] = [
      WebformSubmissionStorageInterface::PURGE_ALL,
      [
        [TRUE, TRUE],
        [TRUE, TRUE],
        [TRUE, FALSE],
        [FALSE, TRUE],
        [FALSE, FALSE],
      ],
      [1, 2],
    ];

    return $tests;
  }

}
