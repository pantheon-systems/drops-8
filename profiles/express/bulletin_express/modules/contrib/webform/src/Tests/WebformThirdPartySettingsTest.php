<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform third party settings.
 *
 * @group Webform
 */
class WebformThirdPartySettingsTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform third party settings.
   */
  public function testThirdPartySettings() {
    $this->drupalLogin($this->adminWebformUser);

    // Check 'Webform: Settings: Third party' shows no modules installed.
    $this->drupalGet('admin/structure/webform/settings/third-party');
    $this->assertRaw('There are no third party settings available.');

    // Check 'Contact: Settings: Third party' shows no modules installed.
    $this->drupalGet('admin/structure/webform/manage/contact/third-party-settings');
    $this->assertRaw('There are no third party settings available.');

    // Install test third party settings module.
    \Drupal::service('module_installer')->install(['webform_test_third_party_settings']);

    // Check 'Webform: Settings: Third party' shows no modules installed.
    $this->drupalGet('admin/structure/webform/settings/third-party');
    $this->assertNoRaw('There are no third party settings available.');

    // Check 'Contact: Settings: Third party' shows no modules installed.
    $this->drupalGet('admin/structure/webform/manage/contact/third-party-settings');
    $this->assertNoRaw('There are no third party settings available.');

    // Check 'Webform: Settings: Third party' message.
    $edit = [
      'third_party_settings[webform_test_third_party_settings][message]' => 'Message for all webforms',
    ];
    $this->drupalPostForm('admin/structure/webform/settings/third-party', $edit, t('Save configuration'));
    $this->drupalGet('webform/contact');
    $this->assertRaw('Message for all webforms');

    // Check that webform.settings.yml contain message.
    $this->assertEqual(
      'Message for all webforms',
      $this->config('webform.settings')->get('third_party_settings.webform_test_third_party_settings.message')
    );

    // Check 'Contact: Settings: Third party' message.
    $edit = [
      'third_party_settings[webform_test_third_party_settings][message]' => 'Message for only this webform',
    ];
    $this->drupalPostForm('admin/structure/webform/manage/contact/third-party-settings', $edit, t('Save'));
    $this->drupalGet('webform/contact');
    $this->assertRaw('Message for only this webform');

    // Uninstall test third party settings module.
    \Drupal::service('module_installer')->uninstall(['webform_test_third_party_settings']);
    $this->drupalGet('webform/contact');
    $this->assertNoRaw('Message for only this webform');

    // Check that webform.settings.yml no longer contains message or
    // webform_test_third_party_settings.
    $this->assertNull(
      $this->config('webform.settings')->get('third_party_settings.webform_test_third_party_settings.message')
    );
    $this->assertNull(
      $this->config('webform.settings')->get('third_party_settings.webform_test_third_party_settings')
    );
  }

}
