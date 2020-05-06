<?php

namespace Drupal\Tests\webform\Traits;

use Drupal\webform\WebformInterface;

/**
 * Provides convenience methods for webform submission view access assertions in browser tests.
 */
trait WebformSubmissionViewAccessTrait {

  /**
   * Check user submission access.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param array $accounts
   *   An associative array of test users.
   *
   * @see \Drupal\webform_access\Tests\WebformAccessSubmissionViewsTest::checkUserSubmissionAccess
   */
  protected function checkUserSubmissionAccess(WebformInterface $webform, array $accounts) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = \Drupal::entityTypeManager()
      ->getStorage('webform_submission');

    // Reset the static cache to make sure we are hitting actual fresh access
    // results.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    \Drupal::entityTypeManager()->getAccessControlHandler('webform_submission')->resetCache();

    foreach ($accounts as $account_type => $account) {
      // Login the current user.
      if ($account_type !== 'anonymous_user') {
        $this->drupalLogin($account);
      }

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
      $this->assertSame($expected_sids, $views_sids, "User '" . $account_type . "' access has correct access through view on webform submission entity type.");

      if ($account_type !== 'anonymous_user') {
        $this->drupalLogout();
      }
    }
  }

}
