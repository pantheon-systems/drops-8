<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\webform\Entity\Webform;

/**
 * Test for webform element managed file image handling.
 *
 * @group Webform
 */
class WebformElementManagedFileImageTest extends WebformElementManagedFileTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_image_file', 'test_element_image_file_attach'];

  /**
   * Test image file upload.
   */
  public function testImageFileUpload() {
    $this->drupalLogin($this->rootUser);

    $webform_image_file = Webform::load('test_element_image_file');

    // Get test image.
    $images = $this->getTestFiles('image');
    $image = reset($images);

    // Check image max resolution validation is being applied.
    $edit = [
      'files[webform_image_file_advanced]' => \Drupal::service('file_system')->realpath($image->uri),
    ];
    $this->postSubmission($webform_image_file, $edit);
    $this->assertRaw('The image was resized to fit within the maximum allowed dimensions of <em class="placeholder">20x20</em> pixels.');

    // Get test image attachment.
    $webform_image_file_attach = Webform::load('test_element_image_file_attach');
    $sid = $this->postSubmissionTest($webform_image_file_attach);

    // Check that thumbnail image style is used for the attachment.
    $this->assertRaw("/system/files/styles/thumbnail/private/webform/test_element_image_file_attach/$sid/webform_image_file_attachment.gif");
  }

}
