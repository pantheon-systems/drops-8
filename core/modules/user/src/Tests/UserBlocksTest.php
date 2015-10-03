<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserBlocksTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user blocks.
 *
 * @group user
 */
class UserBlocksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'views');

  /**
   * A user with the 'administer blocks' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('user_login_block');
    $this->drupalLogout($this->adminUser);
  }

   /**
    * Tests that user login block is hidden from user/login.
    */
  function testUserLoginBlockVisibility() {
    // Array keyed list where key being the URL address and value being expected
    // visibility as boolean type.
    $paths = [
      'node' => TRUE,
      'user/login' => FALSE,
      'user/register' => TRUE,
      'user/password' => TRUE,
    ];
    foreach ($paths as $path => $expected_visibility) {
      $this->drupalGet($path);
      $elements = $this->xpath('//div[contains(@class,"block-user-login-block") and @role="form"]');
      if ($expected_visibility) {
        $this->assertTrue(!empty($elements), 'User login block in path "' . $path . '" should be visible');
     }
      else {
        $this->assertTrue(empty($elements), 'User login block in path "' . $path . '" should not be visible');
      }
    }
  }

  /**
   * Test the user login block.
   */
  function testUserLoginBlock() {
    // Make sure the validation error is displayed when try to login with
    // invalid username/password.
    $edit['name'] = $this->randomMachineName();
    $edit['pass'] = $this->randomMachineName();
    $this->drupalPostForm('node', $edit, t('Log in'));
    $this->assertRaw('1 error has been found:');
    $this->assertRaw('<a href="#edit-name">Username</a>');
    $this->assertText(t('Sorry, unrecognized username or password.'));

    // Create a user with some permission that anonymous users lack.
    $user = $this->drupalCreateUser(array('administer permissions'));

    // Log in using the block.
    $edit = array();
    $edit['name'] = $user->getUsername();
    $edit['pass'] = $user->pass_raw;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');

    // Check that we are still on the same page.
    $this->assertUrl(\Drupal::url('user.admin_permissions', [], ['absolute' => TRUE]), [], 'Still on the same page after login for access denied page');

    // Now, log out and repeat with a non-403 page.
    $this->drupalLogout();
    $this->drupalPostForm('filter/tips', $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');
    $this->assertPattern('!<title.*?' . t('Compose tips') . '.*?</title>!', 'Still on the same page after login for allowed page');

    // Check that the user login block is not vulnerable to information
    // disclosure to third party sites.
    $this->drupalLogout();
    $this->drupalPostForm('http://example.com/', $edit, t('Log in'), array('external' => FALSE));
    // Check that we remain on the site after login.
    $this->assertUrl($user->url('canonical', ['absolute' => TRUE]), [], 'Redirected to user profile page after login from the frontpage');
  }

  /**
   * Test the Who's Online block.
   */
  function testWhosOnlineBlock() {
    $block = $this->drupalPlaceBlock('views_block:who_s_online-who_s_online_block');

    // Generate users.
    $user1 = $this->drupalCreateUser(array('access user profiles'));
    $user2 = $this->drupalCreateUser(array());
    $user3 = $this->drupalCreateUser(array());

    // Update access of two users to be within the active timespan.
    $this->updateAccess($user1->id());
    $this->updateAccess($user2->id(), REQUEST_TIME + 1);

    // Insert an inactive user who should not be seen in the block, and ensure
    // that the admin user used in setUp() does not appear.
    $inactive_time = REQUEST_TIME - (15 * 60) - 1;
    $this->updateAccess($user3->id(), $inactive_time);
    $this->updateAccess($this->adminUser->id(), $inactive_time);

    // Test block output.
    \Drupal::currentUser()->setAccount($user1);
    $content = entity_view($block, 'block');
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($content));
    $this->assertRaw(t('2 users'), 'Correct number of online users (2 users).');
    $this->assertText($user1->getUsername(), 'Active user 1 found in online list.');
    $this->assertText($user2->getUsername(), 'Active user 2 found in online list.');
    $this->assertNoText($user3->getUsername(), 'Inactive user not found in online list.');
    $this->assertTrue(strpos($this->getRawContent(), $user1->getUsername()) > strpos($this->getRawContent(), $user2->getUsername()), 'Online users are ordered correctly.');
  }

  /**
   * Updates the access column for a user.
   */
  private function updateAccess($uid, $access = REQUEST_TIME) {
    db_update('users_field_data')
      ->condition('uid', $uid)
      ->fields(array('access' => $access))
      ->execute();
  }
}
