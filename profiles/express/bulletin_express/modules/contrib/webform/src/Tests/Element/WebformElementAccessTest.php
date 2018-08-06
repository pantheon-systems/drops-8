<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element access.
 *
 * @group Webform
 */
class WebformElementAccessTest extends WebformTestBase {

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $webform = Webform::load('test_element_access');

    // Check user from USER:1 to admin submission user.
    $elements = $webform->get('elements');
    $elements = str_replace('      - 1', '      - ' . $this->adminSubmissionUser->id(), $elements);
    $elements = str_replace('USER:1', 'USER:' . $this->adminSubmissionUser->id(), $elements);
    $webform->set('elements', $elements);
    $webform->save();

    // Create a webform submission.
    $this->drupalLogin($this->normalUser);
    $sid = $this->postSubmission($webform);
    $webform_submission = WebformSubmission::load($sid);

    // Check admins have 'administer webform element access' permission.
    $this->drupalLogin($this->adminWebformUser);
    $this->drupalGet('admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertFieldById('edit-properties-access-create-roles-anonymous');

    // Check webform builder don't have 'administer webform element access'
    // permission.
    $this->drupalLogin($this->ownWebformUser);
    $this->drupalGet('admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertNoFieldById('edit-properties-access-create-roles-anonymous');

    /* Create access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet('webform/test_element_access');
    $this->assertFieldByName('access_create_roles_anonymous');
    $this->assertNoFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');

    // Check authenticated access.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('webform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet('webform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertFieldByName('access_create_users');

    /* Update access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertFieldByName('access_update_roles_anonymous');
    $this->assertNoFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');

    // Check authenticated role access.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet("/webform/test_element_access/submissions/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet("/admin/structure/webform/manage/test_element_access/submission/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertFieldByName('access_update_users');

    /* View, Table, Customize, and Download access */

    $urls = [
      ['path' => "/admin/structure/webform/manage/test_element_access/submission/$sid"],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/submissions'],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/submissions/custom'],
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
      $this->assertNoRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');

      // Check authenticated role access.
      $this->drupalLogin($this->adminWebformUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw('access_view_roles (anonymous)');
      $this->assertRaw('access_view_roles (authenticated)');
      $this->assertNoRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');

      // Check admin user access.
      $this->drupalLogin($this->adminSubmissionUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw('access_view_roles (anonymous)');
      $this->assertRaw('access_view_roles (authenticated)');
      $this->assertRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');
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
      $this->drupalLogin($this->normalUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertNoRaw($raw, 'Authenticated user can not access token');

      // Check admin webform access.
      $this->drupalLogin($this->adminWebformUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertRaw($raw, 'Admin webform user can access token');

      // Check admin submission access.
      $this->drupalLogin($this->adminSubmissionUser);
      $this->drupalGet($url['path'], $url['options']);
      $this->assertRaw($raw, 'Admin submission user can access token');
    }
  }

}
