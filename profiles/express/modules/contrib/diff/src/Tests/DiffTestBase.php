<?php

namespace Drupal\diff\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for Diff web tests.
 */
abstract class DiffTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'diff',
    'block',
  ];

  /**
   * Permissions for the admin user.
   *
   * @var array
   */
  protected $adminPermissions = [
    'administer site configuration',
    'administer nodes',
    'administer content types',
    'create article content',
    'edit any article content',
    'view article revisions',
  ];

  /**
   * A user with content administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the Article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Place the blocks that Diff module uses.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    // Make sure HTML Diff is disabled.
    $config = \Drupal::configFactory()->getEditable('diff.settings');
    $config->set('general_settings.layout_plugins.visual_inline.enabled', FALSE)->save();
  }

  /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param bool $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return \Drupal\user\Entity\User|false
   *   Newly created and logged in user object.
   */
  protected function loginAsAdmin(array $additional_permissions = [], $reset_permissions = FALSE) {
    $permissions = $this->adminPermissions;

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
    return $this->adminUser;
  }

}
