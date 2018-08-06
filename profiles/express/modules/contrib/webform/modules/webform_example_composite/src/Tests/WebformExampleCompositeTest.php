<?php

namespace Drupal\webform_example_composite\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform example composite.
 *
 * @group Webform
 */
class WebformExampleCompositeTest extends WebformTestBase {

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
    $this->drupalGet('webform/webform_example_composite');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $this->assertRaw('<label for="edit-webform-example-composite-first-name">First name</label>');
    $this->assertRaw('<input data-webform-composite-id="webform-example-composite--13--first_name" data-drupal-selector="edit-webform-example-composite-first-name" type="text" id="edit-webform-example-composite-first-name" name="webform_example_composite[first_name]" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<label for="edit-webform-example-composite-last-name">Last name</label>');
    $this->assertRaw('<input data-webform-composite-id="webform-example-composite--13--last_name" data-drupal-selector="edit-webform-example-composite-last-name" type="text" id="edit-webform-example-composite-last-name" name="webform_example_composite[last_name]" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<label for="edit-webform-example-composite-date-of-birth">Date of birth</label>');
    $this->assertRaw('<input type="date" data-drupal-selector="edit-webform-example-composite-date-of-birth" data-drupal-date-format="Y-m-d" id="edit-webform-example-composite-date-of-birth" name="webform_example_composite[date_of_birth]" value="" class="form-date" data-drupal-states="{&quot;enabled&quot;:{&quot;[data-webform-composite-id=\u0022webform-example-composite--13--first_name\u0022]&quot;:{&quot;filled&quot;:true},&quot;[data-webform-composite-id=\u0022webform-example-composite--13--last_name\u0022]&quot;:{&quot;filled&quot;:true}}}" />');
    $this->assertRaw('<label for="edit-webform-example-composite-gender">Gender</label>');
    $this->assertRaw('<select data-drupal-selector="edit-webform-example-composite-gender" id="edit-webform-example-composite-gender" name="webform_example_composite[gender]" class="form-select" data-drupal-states="{&quot;enabled&quot;:{&quot;[data-webform-composite-id=\u0022webform-example-composite--13--first_name\u0022]&quot;:{&quot;filled&quot;:true},&quot;[data-webform-composite-id=\u0022webform-example-composite--13--last_name\u0022]&quot;:{&quot;filled&quot;:true}}}"><option value="" selected="selected"></option><option value="Male">Male</option><option value="Female">Female</option><option value="Transgender">Transgender</option></select>');

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
