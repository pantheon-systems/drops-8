<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileEntityFormDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\Core\Database\Database;

/**
 * Tests the user profile entity form display migration.
 *
 * @group migrate_drupal
 */
class MigrateUserProfileEntityFormDisplayTest extends MigrateDrupal6TestBase {

  static $modules = array('link', 'options', 'datetime', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some fields so the data gets stored.
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_color',
      'type' => 'text',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_biography',
      'type' => 'text_long',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_sell_address',
      'type' => 'boolean',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_sold_to',
      'type' => 'list_string',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_bands',
      'type' => 'text',
      'cardinality' => -1,
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_blog',
      'type' => 'link',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_birthdate',
      'type' => 'datetime',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_love_migrations',
      'type' => 'boolean',
    ))->save();

    $migration = entity_load('migration', 'd6_user_profile_entity_form_display');
    $dumps = array(
      $this->getDumpDirectory() . '/ProfileFields.php',
      $this->getDumpDirectory() . '/Users.php',
      $this->getDumpDirectory() . '/ProfileValues.php',
      $this->getDumpDirectory() . '/UsersRoles.php',
      $this->getDumpDirectory() . '/EventTimezones.php',
    );
    $this->prepare($migration, $dumps);

    $field_data = Database::getConnection('default', 'migrate')
      ->select('profile_fields', 'u')
      ->fields('u')
      ->execute()
      ->fetchAll();
    foreach ($field_data as $field) {
      entity_create('field_config', array(
        'label' => $field->title,
        'description' => '',
        'field_name' => $field->name,
        'entity_type' => 'user',
        'bundle' => 'user',
        'required' => 1,
      ))->save();
    }

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileEntityFormDisplay() {
    $display = entity_get_form_display('user', 'user', 'default');

    // Test a text field.
    $component = $display->getComponent('profile_color');
    $this->assertIdentical('text_textfield', $component['type']);

    // Test a list field.
    $component = $display->getComponent('profile_bands');
    $this->assertIdentical('text_textfield', $component['type']);

    // Test a date field.
    $component = $display->getComponent('profile_birthdate');
    $this->assertIdentical('datetime_default', $component['type']);

    // Test PROFILE_PRIVATE field is hidden.
    $this->assertNull($display->getComponent('profile_sell_address'));

    // Test PROFILE_HIDDEN field is hidden.
    $this->assertNull($display->getComponent('profile_sold_to'));

    // Test that a checkbox field has the proper display label setting.
    $component = $display->getComponent('profile_love_migrations');
    $this->assertIdentical('boolean_checkbox', $component['type']);
    $this->assertIdentical(true, $component['settings']['display_label']);
  }

}
