<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission value.
 *
 * @group Webform
 */
class WebformElementSubmittedValueTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_submitted_value'];

  /**
   * Tests submitted value.
   */
  public function testSubmittedValue() {
    $this->drupalLogin($this->rootUser);

    // Create a submission.
    $webform = Webform::load('test_element_submitted_value');
    $sid = $this->postSubmission($webform);

    // Check the option 'three' is selected.
    $this->drupalGet("/webform/test_element_submission_value/submissions/$sid/edit");
    $this->assertRaw('<option value="three" selected="selected">Three</option>');
    $this->assertOptionSelected('edit-select', 'three');
    $this->assertOptionSelected('edit-select-multiple', 'three');
    $this->assertFieldChecked('edit-checkboxes-three');

    // Remove option 'three' from all elements.
    $elements = $webform->getElementsDecoded();
    foreach ($elements as &$element) {
      unset($element['#options']['three']);
    }
    $webform->setElements($elements);
    $webform->save();

    // Check the option 'three' is still available and selected but
    // the label is now just the value.
    $this->drupalGet("/webform/test_element_submission_value/submissions/$sid/edit");
    $this->assertNoRaw('<option value="three" selected="selected">Three</option>');
    $this->assertRaw('<option value="three" selected="selected">three</option>');
    $this->assertOptionSelected('edit-select', 'three');
    $this->assertOptionSelected('edit-select-multiple', 'three');
    $this->assertFieldChecked('edit-checkboxes-three');
  }

}
