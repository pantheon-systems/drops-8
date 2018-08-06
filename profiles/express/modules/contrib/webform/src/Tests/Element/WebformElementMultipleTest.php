<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element multiple.
 *
 * @group Webform
 */
class WebformElementMultipleTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_multiple', 'test_element_multiple_property'];

  /**
   * Tests multiple element.
   */
  public function testMultiple() {

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check processing for all elements.
    $this->drupalPostForm('webform/test_element_multiple', [], t('Submit'));
    $this->assertRaw("webform_multiple_default:
  - One
  - Two
  - Three
webform_multiple_no_sorting:
  - One
  - Two
  - Three
webform_multiple_no_operations:
  - One
  - Two
  - Three
webform_multiple_email_five:
  - example@example.com
  - test@test.com
webform_multiple_datelist: {  }
webform_multiple_name_composite:
  - title: ''
    first: John
    middle: ''
    last: Smith
    suffix: ''
    degree: ''
  - title: ''
    first: Jane
    middle: ''
    last: Doe
    suffix: ''
    degree: ''
webform_multiple_elements_name_item:
  - first_name: John
    last_name: Smith
  - first_name: Jane
    last_name: Doe
webform_multiple_elements_name_table:
  - first_name: John
    last_name: Smith
  - first_name: Jane
    last_name: Doe
webform_multiple_options:
  - value: one
    text: One
  - value: two
    text: Two
webform_multiple_key:
  one:
    text: One
    score: '1'
  two:
    text: Two
    score: '2'
webform_multiple_elements_hidden_table:
  - first_name: John
    id: john
    last_name: Smith
  - first_name: Jane
    id: jane
    last_name: Doe
webform_multiple_elements_flattened:
  - value: one
    text: One
    description: 'This is the number 1.'
  - value: two
    text: Two
    description: 'This is the number 2.'");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('webform/test_element_multiple');

    // Check first tr.
    $this->assertRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-multiple-default-items-0">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-webform-multiple-default-items-0--item- form-item-webform-multiple-default-items-0--item- form-no-label">');
    $this->assertRaw('<label for="edit-webform-multiple-default-items-0-item-" class="visually-hidden">Item value</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-default-items-0-item-" type="text" id="edit-webform-multiple-default-items-0-item-" name="webform_multiple_default[items][0][_item_]" value="One" size="60" maxlength="128" placeholder="Enter value" class="form-text" />');
    $this->assertRaw('<td class="webform-multiple-table--weight"><div class="webform-multiple-table--weight js-form-item form-item js-form-type-number form-type-number js-form-item-webform-multiple-default-items-0-weight form-item-webform-multiple-default-items-0-weight form-no-label">');
    $this->assertRaw('<label for="edit-webform-multiple-default-items-0-weight" class="visually-hidden">Item weight</label>');
    $this->assertRaw('<input class="webform-multiple-sort-weight form-number" data-drupal-selector="edit-webform-multiple-default-items-0-weight" type="number" id="edit-webform-multiple-default-items-0-weight" name="webform_multiple_default[items][0][weight]" value="0" step="1" size="10" />');
    $this->assertRaw('<td class="webform-multiple-table--operations"><input data-drupal-selector="edit-webform-multiple-default-items-0-operations-add" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-add" name="webform_multiple_default_table_add_0"');
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-default-items-0-operations-remove" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-remove" name="webform_multiple_default_table_remove_0"');

    // Check that sorting is disabled.
    $this->assertNoRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-multiple-no-sorting-items-0">');
    $this->assertRaw('<tr data-drupal-selector="edit-webform-multiple-no-sorting-items-0" class="odd">');

    // Check that operations is disabled.
    $this->assertNoRaw('data-drupal-selector="edit-webform-multiple-no-operations-items-0-operations-remove"');

    /**************************************************************************/
    // Validation.
    /**************************************************************************/

    // Check unique #key validation.
    $edit = [
      'webform_multiple_key[items][1][value]' => 'one',
    ];
    $this->drupalPostForm('webform/test_element_multiple', $edit, t('Submit'));
    $this->assertRaw('The <em class="placeholder">Option value</em> \'one\' is already in use. It must be unique.');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check populated 'webform_multiple_default'.
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Three');
    $this->assertFieldByName('webform_multiple_default[items][3][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][4][_item_]', '');

    // Check adding 'four' and 1 more option.
    $edit = [
      'webform_multiple_default[items][3][_item_]' => 'Four',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_multiple_default_table_add');
    $this->assertFieldByName('webform_multiple_default[items][3][_item_]', 'Four');
    $this->assertFieldByName('webform_multiple_default[items][4][_item_]', '');

    // Check add 10 more rows.
    $edit = ['webform_multiple_default[add][more_items]' => 10];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_multiple_default_table_add');
    $this->assertFieldByName('webform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][15][_item_]', '');

    // Check remove 'one' options.
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_multiple_default_table_remove_0');
    $this->assertNoFieldByName('webform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', 'Three');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Four');

    /**************************************************************************/
    // Property (#multiple).
    /**************************************************************************/

    // Check processing.
    $this->drupalPostForm('webform/test_element_multiple_property', [], t('Submit'));
    $this->assertRaw('webform_element_multiple: false
webform_element_multiple_true: true
webform_element_multiple_false: false
webform_element_multiple_custom: 5
webform_element_multiple_disabled: 5');
  }

}
