<?php

namespace Drupal\honeypot\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Honeypot spam protection admin form functionality.
 *
 * @group honeypot
 */
class HoneypotAdminFormTest extends WebTestBase {

  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['honeypot'];

  /**
   * Setup before test.
   */
  public function setUp() {
    // Enable modules required for this test.
    parent::setUp();

    // Set up admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer honeypot',
      'bypass honeypot protection',
    ]);
  }

  /**
   * Test a valid element name.
   */
  public function testElementNameUpdateSuccess() {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Set up form and submit it.
    $edit['element_name'] = "test";
    $this->drupalPostForm('admin/config/content/honeypot', $edit, t('Save configuration'));

    // Form should have been submitted successfully.
    $this->assertText(t('The configuration options have been saved.'), 'Honeypot element name assertion works for valid names.');

    // Set up form and submit it.
    $edit['element_name'] = "test-1";
    $this->drupalPostForm('admin/config/content/honeypot', $edit, t('Save configuration'));

    // Form should have been submitted successfully.
    $this->assertText(t('The configuration options have been saved.'), 'Honeypot element name assertion works for valid names with dashes and numbers.');
  }

  /**
   * Test an invalid element name (invalid first character).
   */
  public function testElementNameUpdateFirstCharacterFail() {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Set up form and submit it.
    $edit['element_name'] = "1test";
    $this->drupalPostForm('admin/config/content/honeypot', $edit, t('Save configuration'));

    // Form submission should fail.
    $this->assertText(t('The element name must start with a letter.'), 'Honeypot element name assertion works for invalid names.');
  }

  /**
   * Test an invalid element name (invalid character in name).
   */
  public function testElementNameUpdateInvalidCharacterFail() {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Set up form and submit it.
    $edit['element_name'] = "special-character-&";
    $this->drupalPostForm('admin/config/content/honeypot', $edit, t('Save configuration'));

    // Form submission should fail.
    $this->assertText(t('The element name cannot contain spaces or other special characters.'), 'Honeypot element name assertion works for invalid names with special characters.');

    // Set up form and submit it.
    $edit['element_name'] = "space in name";
    $this->drupalPostForm('admin/config/content/honeypot', $edit, t('Save configuration'));

    // Form submission should fail.
    $this->assertText(t('The element name cannot contain spaces or other special characters.'), 'Honeypot element name assertion works for invalid names with spaces.');
  }

}
