<?php
namespace Drupal\Tests\user_external_invite\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Invite pages are reachable.
 *
 * @group user_external_invite
 */
class UiPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_external_invite', 'token'];

  /**
   * Tests that the reaction rule listing page works.
   */
  public function testInviteSendPage() {
    // Give user the permission needed to send an invite.
    $account = $this->drupalCreateUser(['invite new user']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/people/invite');
    $this->assertSession()->statusCodeEquals(200);

    // Test that the user can see the page title.
    $this->assertSession()->pageTextContains('Invite User');
  }
}
