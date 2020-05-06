<?php

namespace Drupal\Tests\webform_access\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform access submission views.
 *
 * @group WebformAccess
 */
class WebformAccessSubmissionViewsTest extends WebformAccessBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'webform_test_views'];

  /**
   * Tests webform access submission views.
   */
  public function testWebformAccessSubmissionViewsTest() {
    // Create a test submission for each node and user account.
    $webform = Webform::load('contact');
    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
    $submission_generate = \Drupal::service('webform_submission.generate');
    foreach ($this->nodes as $node) {
      foreach ($this->users as $user) {
        WebformSubmission::create([
          'webform_id' => 'contact',
          'entity_type' => 'node',
          'entity_id' => $node->id(),
          'uid' => $user->id(),
          'data' => $submission_generate->getData($webform),
        ])->save();
      }
    }

    $this->checkUserSubmissionAccess($webform, $this->users);
  }

  /**
   * Check user submission access.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param array $accounts
   *   An associative array of test users.
   *
   * @see \Drupal\Tests\webform\Functional\WebformSubmissionViewsAccessTest::checkUserSubmissionAccess
   */
  protected function checkUserSubmissionAccess(WebformInterface $webform, array $accounts) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    // Reset the static cache to make sure we are hitting actual fresh access
    // results.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    \Drupal::entityTypeManager()->getAccessControlHandler('webform_submission')->resetCache();

    foreach ($accounts as $account_type => $account) {
      // Login the current user.
      $this->drupalLogin($account);

      // Get the webform_test_views_access view and the sid for each
      // displayed record.  Submission access is controlled via the query.
      // @see webform_query_webform_submission_access_alter()
      $this->drupalGet('/admin/structure/webform/test/views_access');

      $views_sids = [];
      foreach ($this->cssSelect('.view .view-content tbody .views-field-sid') as $node) {
        $views_sids[] = $node->getText();
      }
      sort($views_sids);

      $expected_sids = [];

      // Load all webform submissions and check access using the access method.
      // @see \Drupal\webform\WebformSubmissionAccessControlHandler::checkAccess
      $webform_submissions = $webform_submission_storage->loadByEntities($webform);

      foreach ($webform_submissions as $webform_submission) {
        if ($webform_submission->access('view', $account)) {
          $expected_sids[] = $webform_submission->id();
        }
      }

      sort($expected_sids);

      // Check that the views sids is equal to the expected sids.
      $this->assertEqual($expected_sids, $views_sids, "User '" . $account_type . "' access has correct access through view on webform submission entity type.");
    }
  }

}
