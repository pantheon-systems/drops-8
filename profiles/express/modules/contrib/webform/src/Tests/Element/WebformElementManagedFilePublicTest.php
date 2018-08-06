<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Test for webform element managed public file handling (DRUPAL-PSA-2016-003).
 *
 * @see https://www.drupal.org/psa-2016-003
 *
 * @group Webform
 */
class WebformElementManagedFilePublicTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform', 'webform_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set public file upload support for testing.
    $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
    $settings_config->set('file.file_public', TRUE);
    $settings_config->save();
  }

  /**
   * Test public upload protection.
   */
  public function testPublicUpload() {
    // Check status report private file system warning.
    $requirements = webform_requirements('runtime');
    $this->assertEqual($requirements['webform_file_private']['value'], (string) t('Private file system is set.'));

    $this->drupalLogin($this->rootUser);

    // Check element webform warning message for public files.
    $this->drupalGet('admin/structure/webform/manage/test_element_managed_file/element/managed_file_single/edit');
    $this->assertRaw('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.');
    $this->assertFieldById('edit-properties-uri-scheme-public');

    // Check element webform warning message not visible public files.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('file.file_public', FALSE)
      ->save();
    $this->drupalGet('admin/structure/webform/manage/test_element_managed_file/element/managed_file_single/edit');
    $this->assertNoRaw('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.');
    $this->assertNoFieldById('edit-properties-uri-scheme-public');

    /**************************************************************************/
    // NOTE: Unable to test private file upload warning because SimpleTest
    // automatically enables private file uploads.
    /**************************************************************************/

    // Check managed_file element is enabled.
    $this->drupalGet('admin/structure/webform/manage/test_element_managed_file/element/add');
    $this->assertRaw('>File<');

    // Disable managed file element.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.excluded_elements.managed_file', 'managed_file')
      ->save();

    // Check disabled managed_file element remove from add element dialog.
    $this->drupalGet('admin/structure/webform/manage/test_element_managed_file/element/add');
    $this->assertNoRaw('>File<');

    // Check disabled managed_file element warning.
    $this->drupalGet('admin/structure/webform/manage/test_element_managed_file');
    $this->assertRaw('<em class="placeholder">managed_file_single</em> is a <em class="placeholder">File</em> element, which has been disabled and will not be rendered.');
    $this->assertRaw('<em class="placeholder">managed_file_multiple</em> is a <em class="placeholder">File</em> element, which has been disabled and will not be rendered.');
  }

}
