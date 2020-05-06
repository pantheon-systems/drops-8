<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform checkbox value element.
 *
 * @group Webform
 */
class WebformElementCheckboxValueTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_checkbox_value'];

  /**
   * Tests checkbox value element.
   */
  public function testCheckboxValue() {
    $webform = Webform::load('test_element_checkbox_value');

    // Check submitted values.
    $this->postSubmission($webform);
    $this->assertRaw("checkbox_value_empty: ''
checkbox_value_filled: '{default_value}'
checkbox_value_select_other: Four");

    // Check validation.
    $edit = [
      'checkbox_value_empty[checkbox]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('Enter a value field is required.');

  }

}
