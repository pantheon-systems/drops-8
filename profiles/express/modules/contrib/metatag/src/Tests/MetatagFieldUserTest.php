<?php

namespace Drupal\metatag\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\metatag\Tests\MetatagFieldTestBase;

/**
 * Ensures that the Metatag field works correctly on users.
 *
 * @group metatag
 */
class MetatagFieldUserTest extends MetatagFieldTestBase {

  /**
   * {@inheritDoc}
   */
  public static $modules = [
    // Needed for token handling.
    'token',

    // Needed for the field UI testing.
    'field_ui',

    // Needed to verify that nothing is broken for unsupported entities.
    'contact',

    // The base module.
    'metatag',

    // Some extra custom logic for testing Metatag.
    'metatag_test_tag',

    // Manages the entity type that is being tested.
    'user',
  ];

  /**
   * {@inheritDoc}
   */
  protected $entity_perms = [
    // From Field UI.
    'administer user fields',

    // From User.
    'administer account settings',
    'administer users',
  ];

  /**
   * {@inheritDoc}
   */
  protected $entity_type = 'user';

  /**
   * {@inheritDoc}
   */
  protected $entity_label = 'User';

  /**
   * {@inheritDoc}
   */
  protected $entity_bundle = 'user';

  /**
   * {@inheritDoc}
   */
  protected $entity_add_path = 'admin/people/create';

  /**
   * {@inheritDoc}
   */
  protected $entity_field_admin_path = 'admin/config/people/accounts/fields';

  /**
   * {@inheritDoc}
   */
  protected $entity_title_field = 'name';

  /**
   * {@inheritDoc}
   */
  protected $entity_save_button_label = 'Create new account';

  /**
   * {@inheritDoc}
   */
  protected function entity_default_values($title = 'Barfoo') {
    $password = $this->randomString(16);
    return [
      'mail' => 'test' . $this->adminUser->getEmail(),
      'name' => $title,
      'pass[pass1]' => $password,
      'pass[pass2]' => $password,
    ];
  }

  /**
   * Confirm the metatag field can be shown on a user registration page.
   *
   * @todo
   */
  // protected function testFieldsOnUserRegistrationForm() {}

  /**
   * Confirm the metatag field can be shown on a normal user's own edit form.
   *
   * @todo
   */
  // protected function testFieldsOnUserEditForm() {}

}
