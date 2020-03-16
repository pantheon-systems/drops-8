<?php

namespace Drupal\Tests\metatag\Functional;

/**
 * Ensures that the Metatag field works correctly on users.
 *
 * @group metatag
 */
class MetatagFieldUserTest extends MetatagFieldTestBase {

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  protected $entityPerms = [
    // From Field UI.
    'administer user fields',

    // From User.
    'administer account settings',
    'administer users',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'user';

  /**
   * {@inheritdoc}
   */
  protected $entityLabel = 'User';

  /**
   * {@inheritdoc}
   */
  protected $entityBundle = 'user';

  /**
   * {@inheritdoc}
   */
  protected $entityAddPath = 'admin/people/create';

  /**
   * {@inheritdoc}
   */
  protected $entityFieldAdminPath = 'admin/config/people/accounts/fields';

  /**
   * {@inheritdoc}
   */
  protected $entityTitleField = 'name';

  /**
   * {@inheritdoc}
   */
  protected $entitySaveButtonLabel = 'Create new account';

  /**
   * {@inheritdoc}
   */
  protected function entityDefaultValues($title = 'Barfoo') {
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
  public function testFieldsOnUserRegistrationForm() {}

  /**
   * Confirm the metatag field can be shown on a normal user's own edit form.
   *
   * @todo
   */
  public function testFieldsOnUserEditForm() {}

}
