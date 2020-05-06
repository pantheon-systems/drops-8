<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform element access.
 *
 * @group Webform
 */
class WebformElementAccessTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_access'];

  /**
   * Test element access.
   */
  public function testAccess() {
    $normal_user = $this->drupalCreateUser([
      'access user profiles',
    ]);

    $admin_submission_user = $this->drupalCreateUser([
      'access user profiles',
      'administer webform submission',
    ]);

    $own_submission_user = $this->drupalCreateUser([
      'access user profiles',
      'access webform overview',
      'create webform',
      'edit own webform',
      'delete own webform',
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
    ]);

    $webform = Webform::load('test_element_access');

    /**************************************************************************/

    // Check user from USER:1 to admin submission user.
    $elements = $webform->get('elements');
    $elements = str_replace('      - 1', '      - ' . $admin_submission_user->id(), $elements);
    $elements = str_replace('USER:1', 'USER:' . $admin_submission_user->id(), $elements);
    $webform->set('elements', $elements);
    $webform->save();

    // Create a webform submission.
    $this->drupalLogin($normal_user);
    $sid = $this->postSubmission($webform);
    $webform_submission = WebformSubmission::load($sid);

    // Check admins have 'administer webform element access' permission.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertFieldById('edit-properties-access-create-roles-anonymous', NULL);

    // Check webform builder don't have 'administer webform element access'
    // permission.
    $this->drupalLogin($own_submission_user);
    $this->drupalGet('/admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertNoFieldById('edit-properties-access-create-roles-anonymous', NULL);

    /* Create access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_element_access');
    $this->assertFieldByName('access_create_roles_anonymous');
    $this->assertNoFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');
    $this->assertNoFieldByName('access_create_permissions');

    // Check authenticated access.
    $this->drupalLogin($normal_user);
    $this->drupalGet('/webform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');
    $this->assertFieldByName('access_create_permissions');

    // Check admin user access.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/webform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertFieldByName('access_create_users');
    $this->assertFieldByName('access_create_permissions');

    /* Update access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertFieldByName('access_update_roles_anonymous');
    $this->assertNoFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');
    $this->assertNoFieldByName('access_update_permissions');

    // Check authenticated role access.
    $this->drupalLogin($normal_user);
    $this->drupalGet("/webform/test_element_access/submissions/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');
    $this->assertFieldByName('access_update_permissions');

    // Check admin user access.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet("/admin/structure/webform/manage/test_element_access/submission/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertFieldByName('access_update_users');
    $this->assertFieldByName('access_update_permissions');

    /* View, Table, and Download access */

    $urls = [
      ['path' => "/admin/structure/webform/manage/test_element_access/submission/$sid"],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/submissions'],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/download'],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/download', 'options' => ['query' => ['download' => 1]]],
    ];
    foreach ($urls as $url) {
      $url += ['options' => []];

      // Check anonymous role access.
      $this->drupalLogout();
      $this->drupalGet($url['path'], $url['options']);
      $this->assertRaw('access_view_roles (anonymous)');
      $this->assertNoRaw('access_view_roles (authenticated)');
      $this->assertNoRaw('access_view_users (USER:' . $admin_submission_user->id() . ')');
      $this->assertNoRaw('access_view_permissions (access user profiles)');

      // Check authenticated role access.
      $this->drupalLogin($this->rootUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw('access_view_roles (anonymous)');
      $this->assertRaw('access_view_roles (authenticated)');
      $this->assertNoRaw('access_view_users (USER:' . $admin_submission_user->id() . ')');
      $this->assertRaw('access_view_permissions (access user profiles)');

      // Check admin user access.
      $this->drupalLogin($admin_submission_user);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw('access_view_roles (anonymous)');
      $this->assertRaw('access_view_roles (authenticated)');
      $this->assertRaw('access_view_users (USER:' . $admin_submission_user->id() . ')');
      $this->assertRaw('access_view_permissions (access user profiles)');
    }

    /* Download token access */
    $urls = [
      '<td>token</td>' => [
        'path' => '/admin/structure/webform/manage/test_element_access/results/download',
      ],
      ',Token,' => [
        'path' => '/admin/structure/webform/manage/test_element_access/results/download',
        'options' => ['query' => ['download' => 1, 'excluded_columns' => '']],
      ],
    ];
    foreach ($urls as $raw => $url) {
      $url += ['options' => []];

      // Check anonymous role access.
      $this->drupalLogout();
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw($raw, 'Anonymous user can not access token');

      // Check authenticated role access.
      $this->drupalLogin($normal_user);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw($raw, 'Authenticated user can not access token');

      // Check admin webform access.
      $this->drupalLogin($this->rootUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertRaw($raw, 'Admin webform user can access token');

      // Check admin submission access.
      $this->drupalLogin($admin_submission_user);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertRaw($raw, 'Admin submission user can access token');
    }
  }

}
