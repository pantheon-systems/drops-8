<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for same element.
 *
 * @group Webform
 */
class WebformElementSameTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_same'];

  /**
   * Test same element.
   */
  public function testSame() {
    $webform = Webform::load('test_element_same');

    // Check same checked.
    $this->postSubmission($webform);
    $this->assertRaw("textfield_source: '{some value}'
textfield_same: 1
textfield_destination: '{some value}'
webform_name_source:
  title: Mr
  first: John
  middle: Adam
  last: Smith
  suffix: Jr
  degree: Dr
webform_name_same: 1
webform_name_destination:
  title: Mr
  first: John
  middle: Adam
  last: Smith
  suffix: Jr
  degree: Dr
textfield_multiple_source:
  - '{one value}'
  - '{two value}'
textfield_multiple_same: 1
textfield_multiple_destination:
  - '{one value}'
  - '{two value}'");

    // Check same not checked throw validate errors.
    $edit = [
      'textfield_same' => FALSE,
      'webform_name_same' => FALSE,
      'textfield_multiple_same' => FALSE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('textfield_destination field is required.');
    $this->assertRaw('webform_name_destination field is required.');
    $this->assertRaw('textfield_multiple_destination field is required.');

    // Check same not checked throw validate errors.
    $edit = [
      'textfield_same' => FALSE,
      'textfield_destination' => '{some other value}',
      'webform_name_same' => FALSE,
      'webform_name_destination[title][select]' => 'Mrs',
      'webform_name_destination[first]' => '{first}',
      'webform_name_destination[middle]' => '{middle}',
      'webform_name_destination[last]' => '{last}',
      'webform_name_destination[suffix]' => '{suffix}',
      'webform_name_destination[degree]' => '{degree}',
      'textfield_multiple_same' => FALSE,
      'textfield_multiple_destination[items][0][_item_]' => '{three value}',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $this->assertRaw("textfield_source: '{some value}'
textfield_same: 0
textfield_destination: '{some other value}'
webform_name_source:
  title: Mr
  first: John
  middle: Adam
  last: Smith
  suffix: Jr
  degree: Dr
webform_name_same: 0
webform_name_destination:
  title: Mrs
  first: '{first}'
  middle: '{middle}'
  last: '{last}'
  suffix: '{suffix}'
  degree: '{degree}'
textfield_multiple_source:
  - '{one value}'
  - '{two value}'
textfield_multiple_same: 0
textfield_multiple_destination:
  - '{three value}'");

    $webform_submission = WebformSubmission::load($sid);

    /**************************************************************************/

    // Check textfield source and destination are not equal.
    $this->assertNotEqual(
      $webform_submission->getElementData('textfield_source'),
      $webform_submission->getElementData('textfield_destination')
    );

    // Set textfield same as to TRUE.
    // @see \Drupal\webform\Plugin\WebformElement\WebformSame::preSave
    $webform_submission->setElementData('textfield_same', TRUE);
    $webform_submission->save();

    // Check textfield source and destination are now equal.
    $this->assertEqual(
      $webform_submission->getElementData('textfield_source'),
      $webform_submission->getElementData('textfield_destination')
    );
  }

}
