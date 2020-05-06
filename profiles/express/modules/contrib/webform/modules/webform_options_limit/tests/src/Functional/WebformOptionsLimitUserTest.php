<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform options limit test.
 *
 * @group webform_browser
 */
class WebformOptionsLimitUserTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit user.
   */
  public function testOptionsLimitUserTest() {
    $webform = Webform::load('test_handler_options_limit_user');

    // Create authenticated user.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Check that options limit is not met for authenticated user.
    $this->drupalGet('/webform/test_handler_options_limit_user');
    $this->assertRaw('A [1 remaining]');
    $this->assertRaw('B [2 remaining]');
    $this->assertRaw('C [3 remaining]');
    $this->assertNoRaw('options_limit_user is not available.');

    // Check that options limit is reached for authenticated user.
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('B [0 remaining]');
    $this->assertRaw('C [0 remaining]');
    $this->assertRaw('options_limit_user is not available.');

    // Create another authenticated user.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Check that options limit is not met for authenticated user.
    $this->drupalGet('/webform/test_handler_options_limit_user');
    $this->assertRaw('A [1 remaining]');
    $this->assertRaw('B [2 remaining]');
    $this->assertRaw('C [3 remaining]');
    $this->assertNoRaw('options_limit_user is not available.');

    // Check that options limit is reached for authenticated user.
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('B [0 remaining]');
    $this->assertRaw('C [0 remaining]');
    $this->assertRaw('options_limit_user is not available.');

    // Logout.
    // NOTE:
    // We are are testing anonymous user last because anonymous
    // submission are transfered to authenticated users when they login.
    $this->drupalLogout();

    // Check that options limit is not met for anonymous user.
    $this->drupalGet('/webform/test_handler_options_limit_user');
    $this->assertRaw('A [1 remaining]');
    $this->assertRaw('B [2 remaining]');
    $this->assertRaw('C [3 remaining]');
    $this->assertNoRaw('options_limit_user is not available.');

    // Check that options limit is reached for anonymous user.
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('B [0 remaining]');
    $this->assertRaw('C [0 remaining]');
    $this->assertRaw('options_limit_user is not available.');

    // Check that Options limit report is not available.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit_user/results/options-limit');
    $this->assertResponse(403);
  }

}
