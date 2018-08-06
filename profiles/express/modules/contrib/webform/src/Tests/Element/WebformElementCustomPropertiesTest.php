<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for the webform element custom properties.
 *
 * @group Webform
 */
class WebformElementCustomPropertiesTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_ui', 'webform_test_custom_properties'];

  /**
   * Tests element custom properties.
   */
  public function testCustomProperties() {
    // Create and login admin user.
    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);
    $this->drupalLogin($admin_user);

    // Get Webform storage.
    $webform_storage = \Drupal::entityTypeManager()->getStorage('webform');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webform_storage->load('contact');

    // Set name element.
    $name_element = [
      '#type' => 'textfield',
      '#title' => 'Your Name',
      '#default_value' => '[webform-authenticated-user:display-name]',
      '#required' => TRUE,
    ];

    // Check that name element render array does not contain custom property
    // or data.
    $this->assertEqual($webform->getElementDecoded('name'), $name_element);

    // Check that name input does not contain custom data.
    $this->drupalGet('webform/contact');
    $this->assertRaw('<input data-drupal-selector="edit-name" type="text" id="edit-name" name="name" value="' . htmlentities($admin_user->label()) . '" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Submit empty custom property and data.
    $edit = [
      'properties[custom_data]' => '',
    ];
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/name/edit', $edit, t('Save'));

    // Get updated contact webform.
    $webform_storage->resetCache();
    $webform = $webform_storage->load('contact');

    // Check that name element render array still does not contain custom
    // property or data.
    $this->assertEqual($webform->getElementDecoded('name'), $name_element);

    // Add custom property and data.
    $edit = [
      'properties[custom_data]' => 'custom-data',
    ];
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/name/edit', $edit, t('Save'));

    // Get updated contact webform.
    $webform_storage->resetCache();
    $webform = $webform_storage->load('contact');

    // Check that name element does contain custom property or data.
    $name_element += [
      '#custom_data' => 'custom-data',
    ];
    $this->assertEqual($webform->getElementDecoded('name'), $name_element);

    // Check that name input does contain custom data.
    $this->drupalGet('webform/contact');
    $this->assertRaw('<input data-custom="custom-data" data-drupal-selector="edit-name" type="text" id="edit-name" name="name" value="' . htmlentities($admin_user->label()) . '" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');
  }

}
