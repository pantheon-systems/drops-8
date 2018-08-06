<?php

namespace Drupal\Tests\webform\Kernel\Entity;

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installConfig('webform');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Tests some of the methods.
   */
  public function testWebformMethods() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create(['id' => 'webform_test', 'title' => 'Test']);
    $elements = [
      'name' => [
        '#type' => 'textfield',
        '#title' => 'name',
      ],
      'other' => [
        '#type' => 'textfield',
        '#title' => 'other',
      ],
    ];
    $webform->setElements($elements);
    $webform->save();
    $webform->save();

    // Create webform submission.
    $values = [
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
      'data' => ['name' => 'John Smith'],
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    $webform_submission->save();
    $this->assertEquals($webform->uuid(), $webform_submission->getWebform()->uuid());

    // Check get data.
    $this->assertEquals($webform_submission->getData(), ['name' => 'John Smith']);

    // Check get element data.
    $this->assertEquals($webform_submission->getElementData('name'), 'John Smith');

    // Check get element data.
    $this->assertEquals($webform_submission->getElementData('name'), 'John Smith');

    // Check set element data.
    $webform_submission->setElementData('other', 'Other');
    $this->assertEquals($webform_submission->getElementData('other'), 'Other');
    $this->assertEquals($webform_submission->getData(), ['name' => 'John Smith', 'other' => 'Other']);

    // Check default submission label.
    $this->assertEquals($webform_submission->label(), 'Test: Submission #1');

    // Check customizing admin settings submission label.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_submission_label', 'Submission #[webform_submission:serial]')
      ->save();
    $this->assertEquals($webform_submission->label(), 'Submission #1');

    // Check customizing webform specific submission label.
    $webform = $webform_submission->getWebform();
    $webform->setSetting('submission_label', 'Submitted by [webform_submission:values:name]')
      ->save();
    $this->assertEquals($webform->getSetting('submission_label'), 'Submitted by [webform_submission:values:name]');
    $this->assertEquals($webform_submission->label(), 'Submitted by John Smith');

    // @todo Add the below assertions.
    // Check source entity.
    // Check create submission.
    // Check save submission.
  }

}
