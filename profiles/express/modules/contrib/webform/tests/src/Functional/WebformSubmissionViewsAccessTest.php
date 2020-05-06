<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\webform\Traits\WebformSubmissionViewAccessTrait;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests access rules in the context of webform submission views access.
 *
 * @group webform_browser
 */
class WebformSubmissionViewsAccessTest extends BrowserTestBase {

  use WebformSubmissionViewAccessTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'views',
    'webform',
    'webform_test_views',
  ];

  /**
   * Test webform submission entity access in a view query.
   */
  public function testEntityAccess() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Create any access user, own access user, and no (anonymous) access user.
    $any_user = $this->drupalCreateUser(['access webform overview']);
    $own_user = $this->drupalCreateUser(['access webform overview']);
    $without_access_user = $this->drupalCreateUser(['access webform overview']);

    // Grant any and own access to submissions.
    $webform->setAccessRules([
      'view_any' => ['users' => [$any_user->id()]],
      'view_own' => ['users' => [$own_user->id()]],
    ])->save();

    // Create an array of the accounts.
    $accounts = [
      'any_user' => $any_user,
      'own_user' => $own_user,
      'without_access' => $without_access_user,
    ];

    // Create test submissions.
    $this->createSubmissions($webform, $accounts);

    // Check user submission access.
    $this->checkUserSubmissionAccess($webform, $accounts);

    // Clear webform access rules.
    $webform->setAccessRules([])->save();

    // Check user submission access cache is cleared.
    $this->checkUserSubmissionAccess($webform, $accounts);
  }

  /**
   * Tests webform submission views enforce access per user's permissions.
   */
  public function testPermissionAccess() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Create anonymous, any access user, own access user, and no (anonymous) access user.
    $anonymous_user = User::getAnonymousUser();
    user_role_grant_permissions('anonymous', [
      'access webform overview',
      'view own webform submission',
    ]);
    $own_webform_user = $this->drupalCreateUser([
      'access webform overview',
      'edit own webform',
    ]);
    $webform->setOwner($own_webform_user)->save();
    $any_submission_user = $this->drupalCreateUser([
      'access webform overview',
      'view any webform submission',
    ]);
    $own_submission_user = $this->drupalCreateUser([
      'access webform overview',
      'view own webform submission',
    ]);
    $without_access_user = $this->drupalCreateUser([
      'access webform overview',
    ]);

    // Create an array of the accounts.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = [
      'anonymous_user' => $anonymous_user,
      'own_webform_user' => $own_webform_user,
      'any_submission_user' => $any_submission_user,
      'own_submission_user' => $own_submission_user,
      'without_access' => $without_access_user,
    ];

    // Create test submissions.
    $this->createSubmissions($webform, $accounts);

    // Check user submission access.
    $this->checkUserSubmissionAccess($webform, $accounts);

    // Clear any and own permissions for all accounts.
    foreach ($accounts as $account_type => &$account) {
      if ($account_type === 'anonymous_user') {
        $rid = 'anonymous';
      }
      else {
        $roles = $account->getRoles(TRUE);
        $rid = reset($roles);
      }
      user_role_revoke_permissions($rid, [
        'view any webform submission',
        'view own webform submission',
        'edit own webform',
      ]);
    }

    // Check user submission access cache is cleared.
    $this->checkUserSubmissionAccess($webform, $accounts);
  }

  /**
   * Create test a submission for each account.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param array $accounts
   *   An associative array of test users.
   */
  protected function createSubmissions(WebformInterface $webform, array $accounts) {
    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
    $submission_generate = \Drupal::service('webform_submission.generate');

    // Create a test submission for each user account.
    foreach ($accounts as $account) {
      WebformSubmission::create([
        'webform_id' => $webform->id(),
        'uid' => $account->id(),
        'data' => $submission_generate->getData($webform),
      ])->save();
    }
  }

}
