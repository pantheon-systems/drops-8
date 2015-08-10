<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserContactSettingsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Users contact settings migration.
 *
 * @group migrate_drupal
 */
class MigrateUserContactSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('user', array('users_data'));

    $this->loadDumps([
      'Users.php',
      'ProfileValues.php',
      'UsersRoles.php',
      'EventTimezones.php',
    ]);

    $id_mappings = array(
      'd6_user' => array(
        array(array(2), array(2)),
        array(array(8), array(8)),
        array(array(15), array(15)),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $this->executeMigration('d6_user_contact_settings');
  }

  /**
   * Tests the Drupal6 user contact settings migration.
   */
  public function testUserContactSettings() {
    $user_data = \Drupal::service('user.data');
    $module = $key = 'contact';
    $uid = 2;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical('1', $setting);

    $uid = 8;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical('0', $setting);

    $uid = 15;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical(NULL, $setting);
  }

}
