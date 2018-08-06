<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests the webform entity class.
 *
 * @group webform
 * @see \Drupal\webform\Entity\WebformSubmission
 */
class WebformSubmissionEntityTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'webform', 'user', 'field'];

  /**
   * Test missing webform id exception.
   *
   * @expectedException \Exception
   */
  public function testMissingWebformIdException() {
    WebformSubmission::create();
  }

  /**
   * Tests some of the methods.
   */
  public function testWebformMethods() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create(['id' => 'webform_test']);
    $webform->save();

    // Create webform submission.
    $values = [
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    $this->assertEquals($webform->uuid(), $webform_submission->getWebform()->uuid());

    // @todo Add the below assertions.
    // Check source entity.
    // Check create submission.
    // Check save submission.
  }

}
