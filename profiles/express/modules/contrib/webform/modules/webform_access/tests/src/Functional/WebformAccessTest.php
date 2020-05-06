<?php

namespace Drupal\Tests\webform_access\Functional;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests for webform access.
 *
 * @group WebformAccess
 */
class WebformAccessTest extends WebformAccessBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Tests webform access.
   */
  public function testWebformAccess() {
    $nid = $this->nodes['contact_01']->id();

    $this->drupalLogin($this->rootUser);

    // Check that employee and manager groups exist.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertLink('employee_group');
    $this->assertLink('manager_group');

    // Check that webform node is assigned to groups.
    $this->assertLink($this->nodes['contact_01']->label());

    // Check that employee and manager users can't access webform results.
    foreach ($this->users as $account) {
      $this->drupalLogin($account);
      $this->drupalGet("/node/$nid/webform/results/submissions");
      $this->assertResponse(403);
    }

    $this->drupalLogin($this->rootUser);

    // Assign users to groups via the UI.
    foreach ($this->groups as $name => $group) {
      $this->drupalPostForm(
        "/admin/structure/webform/access/group/manage/$name",
        ['users[]' => $this->users[$name]->id()],
        t('Save')
      );
    }

    // Check that manager and employee users can access webform results.
    foreach (['manager', 'employee'] as $name) {
      $account = $this->users[$name];
      $this->drupalLogin($account);
      $this->drupalGet("/node/$nid/webform/results/submissions");
      $this->assertResponse(200);
    }

    // Check that employee can't delete results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(403);

    // Check that manager can delete results.
    $this->drupalLogin($this->users['manager']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(200);

    // Unassign employee user from employee group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm(
      '/admin/structure/webform/access/group/manage/employee',
      ['users[]' => 1],
      t('Save')
    );

    // Assign employee user to manager group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm(
      '/user/' . $this->users['employee']->id() . '/edit',
      ['webform_access_group[]' => 'manager'],
      t('Save')
    );

    // Check defining webform field's access groups default value.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/types');
    $this->drupalGet('/admin/structure/types/manage/webform/fields');
    $this->drupalPostForm(
      '/admin/structure/types/manage/webform/fields/node.webform.webform',
      [
        'default_value_input[webform][0][target_id]' => 'contact',
        'default_value_input[webform][0][settings][default_data]' => 'test: test',
        'default_value_input[webform][0][settings][webform_access_group][]' => 'manager',
      ],
      t('Save settings')
    );
    $this->drupalGet('/node/add/webform');
    $this->assertFieldByName('webform[0][settings][webform_access_group][]', 'manager');

    // Check that employee can now delete results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(200);

    // Unassign node from groups.
    $this->drupalLogin($this->rootUser);
    foreach ($this->groups as $name => $group) {
      $this->drupalPostForm(
        "/admin/structure/webform/access/group/manage/$name",
        ['entities[]' => 'node:' . $this->nodes['contact_02']->id() . ':webform:contact'],
        t('Save')
      );
    }

    // Check that employee can't access results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(403);

    // Assign webform node to group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm(
      "/node/$nid/edit",
      ['webform[0][settings][webform_access_group][]' => 'manager'],
      t('Save')
    );

    // Check that employee can now access results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(200);

    // Delete employee group.
    $this->groups['employee']->delete();

    // Check that employee group is configured.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertRaw('manager_type');
    $this->assertLink('manager_group');
    $this->assertLink('manager_user');
    $this->assertLink('employee_user');
    $this->assertLink('contact_01');
    $this->assertLink('contact_02');

    // Reset caches.
    \Drupal::entityTypeManager()->getStorage('webform_access_group')->resetCache();
    \Drupal::entityTypeManager()->getStorage('webform_access_type')->resetCache();

    // Delete types.
    foreach ($this->types as $type) {
      $type->delete();
    }

    // Check that manager type has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoRaw('manager_type');

    // Delete users.
    foreach ($this->users as $user) {
      $user->delete();
    }

    // Check that manager type has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoLink('manager_user');
    $this->assertNoLink('employee_user');

    // Delete contact 2.
    $this->nodes['contact_02']->delete();

    // Check that contact_02 has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoLink('contact_02');

    // Delete webform field config.
    FieldConfig::loadByName('node', 'webform', 'webform')->delete();

    // Check that contact_02 has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoLink('contact_02');
  }

  /**
   * Tests webform administrator access.
   */
  public function testWebformAdministratorAccess() {
    // Check root user access to group edit form.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/access/group/manage/manager');
    $this->assertFieldByName('label');
    $this->assertFieldByName('description[value]');
    $this->assertFieldByName('type');
    $this->assertFieldByName('admins[]');
    $this->assertFieldByName('users[]');
    $this->assertFieldByName('entities[]');
    $this->assertFieldByName('permissions[administer]');

    // Logout.
    $this->drupalLogout();

    // Check access denied to 'Access' tab for anonymous user.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertResponse(403);

    // Login as administrator.
    $administrator = $this->drupalCreateUser();
    $this->drupalLogin($administrator);

    // Check access denied to 'Access' tab for administrator.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertResponse(403);

    // Assign administrator to the 'manager' access group.
    $this->groups['manager']->addAdminId($administrator->id());
    $this->groups['manager']->save();

    // Check access allowed to 'Access' tab for administrator.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertResponse(200);
    $this->assertLink('Manage');
    $this->assertNoLink('Edit');

    // Click 'manager_group' link and move to the group edit form.
    $this->clickLink('manager_group');

    // Check that details information exists.
    $this->assertRaw('<details data-drupal-selector="edit-information" id="edit-information" class="js-form-wrapper form-wrapper">');

    // Check that users element exists.
    $this->assertNoFieldByName('label');
    $this->assertNoFieldByName('description[value]');
    $this->assertNoFieldByName('type');
    $this->assertNoFieldByName('admins[]');
    $this->assertFieldByName('users[]');
    $this->assertNoFieldByName('entities[]');
    $this->assertNoFieldByName('permissions[administer]');
  }

}
