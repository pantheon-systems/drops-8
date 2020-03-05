<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Views;

/**
 * Tests pathauto user UI integration.
 *
 * @group pathauto
 */
class PathautoUserWebTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['pathauto', 'views'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'administer users',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->createPattern('user', '/users/[user:name]');
  }

  /**
   * Basic functional testing of Pathauto with users.
   */
  function testUserEditing() {
    // There should be no Pathauto checkbox on user forms.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertNoFieldById('path[0][pathauto]');
  }

  /**
   * Test user operations.
   */
  function testUserOperations() {
    $account = $this->drupalCreateUser();

    // Delete all current URL aliases.
    $this->deleteAllAliases();

    // Find the position of just created account in the user_admin_people view.
    $view = Views::getView('user_admin_people');
    $view->initDisplay();
    $view->preview('page_1');

    foreach ($view->result as $key => $row) {
      if ($view->field['name']->getValue($row) == $account->getDisplayName()) {
        break;
      }
    }

    $edit = [
      'action' => 'pathauto_update_alias_user',
      "user_bulk_form[$key]" => TRUE,
    ];
    $this->drupalPostForm('admin/people', $edit, t('Apply to selected items'));
    $this->assertText('Update URL alias was applied to 1 item.');

    $this->assertEntityAlias($account, '/users/' . mb_strtolower($account->getDisplayName()));
    $this->assertEntityAlias($this->adminUser, '/user/' . $this->adminUser->id());
  }

}
