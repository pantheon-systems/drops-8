<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for email webform handler email roles functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailRolesTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_roles'];

  /**
   * Test email roles handler.
   */
  public function testEmailRoles() {
    // Enable all authenticated roles.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('mail.roles', ['authenticated'])
      ->save();

    // IMPORTANT: Simpletest create 'administrators' role while Drupal
    // creates 'administrator' role.
    // WORKAROUND: Create 'administrator' role so that SimpleTest and Drupal
    // are in-sync.
    $this->drupalCreateRole([], 'administrator');

    $webform = Webform::load('test_handler_email_roles');

    $authenticated_user = $this->drupalCreateUser();
    $authenticated_user->set('mail', 'authenticated@example.com');
    $authenticated_user->save();

    $blocked_user = $this->drupalCreateUser();
    $blocked_user->set('mail', 'blocked@example.com');
    $blocked_user->block();
    $blocked_user->save();

    $admin_user = $this->drupalCreateUser();
    $admin_user->set('mail', 'administrator@example.com');
    $admin_user->addRole('administrator');
    $admin_user->save();

    // Check email all authenticated users.
    $this->postSubmission($webform, ['role' => 'authenticated']);
    $this->assertRaw('<em class="placeholder">Webform submission from: Test: Handler: Email roles</em> sent to <em class="placeholder">admin@example.com,administrator@example.com,authenticated@example.com</em> from <em class="placeholder">Drupal</em> [<em class="placeholder">simpletest@example.com</em>].');

    // Check that blocked user is never emailed.
    $this->assertNoRaw('blocked@example.com');

    // Check that unblocked user is never emailed.
    $blocked_user->activate()->save();
    $this->postSubmission($webform, ['role' => 'authenticated']);
    $this->assertRaw('blocked@example.com');

    // Check email administrator user.
    $this->postSubmission($webform, ['role' => 'administrator']);
    $this->assertRaw('<em class="placeholder">Webform submission from: Test: Handler: Email roles</em> sent to <em class="placeholder">administrator@example.com</em> from <em class="placeholder">Drupal</em> [<em class="placeholder">simpletest@example.com</em>].');

    // Check that missing 'other' role does not send any emails.
    $this->postSubmission($webform, ['role' => 'other']);
    $this->assertRaw('<em class="placeholder">Test: Handler: Email roles</em>: Email not sent for <em class="placeholder">Email</em> handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.');

    // Check that authenticated role is no longer available.
    // Enable only administrator role.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('mail.roles', ['administrator'])
      ->save();
    $this->postSubmission($webform, ['role' => 'authenticated']);
    $this->assertRaw('<em class="placeholder">Test: Handler: Email roles</em>: Email not sent for <em class="placeholder">Email</em> handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.');
  }

}
