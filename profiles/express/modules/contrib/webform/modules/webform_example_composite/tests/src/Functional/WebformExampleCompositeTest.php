<?php

namespace Drupal\Tests\webform_example_composite\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform example composite.
 *
 * @group Webform
 */
class WebformExampleCompositeTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_example_composite'];

  /**
   * Tests webform example element.
   */
  public function testWebformExampleComposite() {
    $webform = Webform::load('webform_example_composite');

    // Check form element rendering.
    $this->drupalGet('/webform/webform_example_composite');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $this->assertRaw('<label for="edit-webform-example-composite-first-name">First name</label>');
    $this->assertFieldById('edit-webform-example-composite-first-name');
    $this->assertRaw('<label for="edit-webform-example-composite-last-name">Last name</label>');
    $this->assertFieldById('edit-webform-example-composite-last-name');
    $this->assertRaw('<label for="edit-webform-example-composite-date-of-birth">Date of birth</label>');
    $this->assertFieldById('edit-webform-example-composite-date-of-birth');
    $this->assertRaw('<label for="edit-webform-example-composite-gender">Gender</label>');
    $this->assertFieldById('edit-webform-example-composite-gender');

    // Check webform element submission.
    $edit = [
      'webform_example_composite[first_name]' => 'John',
      'webform_example_composite[last_name]' => 'Smith',
      'webform_example_composite[gender]' => 'Male',
      'webform_example_composite[date_of_birth]' => '1910-01-01',
      'webform_example_composite_multiple[items][0][first_name]' => 'Jane',
      'webform_example_composite_multiple[items][0][last_name]' => 'Doe',
      'webform_example_composite_multiple[items][0][gender]' => 'Female',
      'webform_example_composite_multiple[items][0][date_of_birth]' => '1920-12-01',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getElementData('webform_example_composite'), [
      'first_name' => 'John',
      'last_name' => 'Smith',
      'gender' => 'Male',
      'date_of_birth' => '1910-01-01',
    ]);
    $this->assertEqual($webform_submission->getElementData('webform_example_composite_multiple'), [
      [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'gender' => 'Female',
        'date_of_birth' => '1920-12-01',
      ],
    ]);
  }

}
