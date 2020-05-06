<?php

namespace Drupal\Tests\webform\Functional\Composite;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\Element\WebformElementManagedFileTestBase;

/**
 * Tests for custom composite element.
 *
 * @group Webform
 */
class WebformCompositeCustomFileTest extends WebformElementManagedFileTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_composite_custom_file'];

  /**
   * Test custom composite element.
   */
  public function testCustom() {
    $webform = Webform::load('test_composite_custom_file');

    $first_file = $this->files[0];

    /**************************************************************************/

    // Create submission with file.
    $edit = [
      'webform_custom_composite_file[items][0][_item_][textfield]' => '{textfield}',
      'files[webform_custom_composite_file_items_0__item__managed_file]' => \Drupal::service('file_system')->realpath($first_file->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);

    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check file permanent.
    $this->assert($file->isPermanent(), 'Test file is permanent');

    // Check file upload.
    $element_data = $webform_submission->getElementData('webform_custom_composite_file');
    $this->assertEqual($element_data[0]['managed_file'], $fid, 'Test file was upload to the current submission');

    // Check test file file usage.
    $this->assertIdentical(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 1 usage.');

    // Check test file uploaded file path.
    $this->assertEqual($file->getFileUri(), 'private://webform/test_composite_custom_file/' . $sid . '/' . $first_file->filename);

    // Check that test file exists.
    $this->assert(file_exists($file->getFileUri()), 'File exists');
  }

}
