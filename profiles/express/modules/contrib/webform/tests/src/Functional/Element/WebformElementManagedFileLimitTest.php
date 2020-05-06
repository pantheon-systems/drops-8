<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Test for webform element managed file limit.
 *
 * @group Webform
 */
class WebformElementManagedFileLimitTest extends WebformElementManagedFileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file_limit'];

  /**
   * Test file limit.
   */
  public function testLimits() {
    $this->drupalLogin($this->rootUser);

    // Get a 1 MB text file.
    $files = $this->getTestFiles('text', '1024');
    $file = reset($files);
    $bytes = filesize($file->uri);
    $this->debug($bytes);

    $webform = Webform::load('test_element_managed_file_limit');

    // Check form file limit.
    $this->drupalGet('/webform/test_element_managed_file_limit');
    $this->assertRaw('1 MB limit per form.');

    // Check empty form file limit.
    $webform->setSetting('form_file_limit', '')->save();
    $this->drupalGet('/webform/test_element_managed_file_limit');
    $this->assertNoRaw('1 MB limit per form.');

    // Check default form file limit.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_form_file_limit', '2 MB')
      ->save();
    $this->drupalGet('/webform/test_element_managed_file_limit');
    $this->assertRaw('2 MB limit per form.');

    // Set limit to 2 files.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_form_file_limit', ($bytes * 2) . ' bytes')
      ->save();
    $this->drupalGet('/webform/test_element_managed_file_limit');
    $this->assertRaw(format_size($bytes * 2) . ' limit per form.');

    // Check valid file upload.
    $edit = [
      'files[managed_file_01]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);
    $this->assertNotNull($sid);

    // Check invalid file upload.
    $edit = [
      'files[managed_file_01]' => \Drupal::service('file_system')->realpath($file->uri),
      'files[managed_file_02]' => \Drupal::service('file_system')->realpath($file->uri),
      'files[managed_file_03]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('This form\'s file upload quota of <em class="placeholder">2 KB</em> has been exceeded. Please remove some files.');

    // Check invalid composite file upload.
    $edit = [
      'files[managed_file_01]' => \Drupal::service('file_system')->realpath($file->uri),
      'files[custom_composite_managed_files_items_0_managed_file]' => \Drupal::service('file_system')->realpath($file->uri),
      'files[custom_composite_managed_files_items_1_managed_file]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalPostForm('/webform/test_element_managed_file_limit', [], t('Add'));
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertRaw('This form\'s file upload quota of <em class="placeholder">2 KB</em> has been exceeded. Please remove some files.');
  }

}
