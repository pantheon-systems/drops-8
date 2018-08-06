<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform validate multiple.
 *
 * @group Webform
 */
class WebformElementValidateMultipleTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_multiple'];

  /**
   * Tests element validate multiple.
   */
  public function testValidateMultiple() {
    $this->drupalGet('webform/test_element_validate_multiple');

    // Check that only three textfields are displayed.
    $this->assertFieldByName('webform_element_multiple_textfield_three[items][0][_item_]');
    $this->assertFieldByName('webform_element_multiple_textfield_three[items][1][_item_]');
    $this->assertFieldByName('webform_element_multiple_textfield_three[items][2][_item_]');
    $this->assertNoFieldByName('webform_element_multiple_textfield_three[items][3][_item_]');
    $this->assertNoFieldByName('webform_element_multiple_textfield_three_table_add');

    // Post multiple values to checkboxes and select multiple that exceed
    // allowed values.
    $edit = [
      'webform_element_multiple_checkboxes_two[one]' => 'one',
      'webform_element_multiple_checkboxes_two[two]' => 'two',
      'webform_element_multiple_checkboxes_two[three]' => 'three',
      'webform_element_multiple_select_two[]' => ['one', 'two', 'three'],
    ];
    $this->drupalPostForm('webform/test_element_validate_multiple', $edit, t('Submit'));

    // Check checkboxes multiple custom error message.
    $this->assertRaw('Please check only two options.');

    // Check select multiple default error message.
    $this->assertRaw('<em class="placeholder">webform_element_multiple_select_two</em>: this element cannot hold more than 2 values.');
  }

}
