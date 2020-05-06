<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform element multiple.
 *
 * @group Webform
 */
class WebformElementMultipleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_multiple'];

  /**
   * Tests multiple element.
   */
  public function testMultiple() {

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    $webform = Webform::load('test_element_multiple');

    // Check processing for all elements.
    $this->drupalPostForm('/webform/test_element_multiple', [], t('Submit'));
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
webform_multiple_no_add_more:
  - One
  - Two
  - Three
webform_multiple_no_add_more_input:
  - One
  - Two
  - Three
webform_multiple_custom_label:
  - One
  - Two
  - Three
webform_multiple_required:
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
    description: 'This is the number 2.'
webform_multiple_no_items: {  }
webform_multiple_custom_attributes: {  }");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('/webform/test_element_multiple');

    // Check first tr.
    $this->assertRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-multiple-default-items-0">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-webform-multiple-default-items-0--item- form-item-webform-multiple-default-items-0--item- form-no-label">');
    $this->assertRaw('<label for="edit-webform-multiple-default-items-0-item-" class="visually-hidden">Item value</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-default-items-0-item-" type="text" id="edit-webform-multiple-default-items-0-item-" name="webform_multiple_default[items][0][_item_]" value="One" size="60" maxlength="128" placeholder="Enter value…" class="form-text" />');
    $this->assertRaw('<td class="webform-multiple-table--weight"><div class="webform-multiple-table--weight js-form-item form-item js-form-type-number form-type-number js-form-item-webform-multiple-default-items-0-weight form-item-webform-multiple-default-items-0-weight form-no-label">');
    $this->assertRaw('<label for="edit-webform-multiple-default-items-0-weight" class="visually-hidden">Item weight</label>');
    $this->assertRaw('<input class="webform-multiple-sort-weight form-number" data-drupal-selector="edit-webform-multiple-default-items-0-weight" type="number" id="edit-webform-multiple-default-items-0-weight" name="webform_multiple_default[items][0][weight]" value="0" step="1" size="10" />');
    $this->assertRaw('<td class="webform-multiple-table--operations webform-multiple-table--operations-two"><input data-drupal-selector="edit-webform-multiple-default-items-0-operations-add" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-add" name="webform_multiple_default_table_add_0"');
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-default-items-0-operations-remove" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-remove" name="webform_multiple_default_table_remove_0"');

    // Check that sorting is disabled.
    $this->assertNoRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-multiple-no-sorting-items-0">');
    $this->assertRaw('<tr data-drupal-selector="edit-webform-multiple-no-sorting-items-0" class="odd">');

    // Check that add more is removed.
    $this->assertFieldByName('webform_multiple_no_operations[add][more_items]', '1');
    $this->assertNoFieldByName('webform_multiple_no_add_more_table_add', 'Add');
    $this->assertNoFieldByName('webform_multiple_no_add_more[add][more_items]', '1');

    // Check that add more input is removed.
    $this->assertFieldByName('webform_multiple_no_add_more_input_table_add', 'Add');
    $this->assertNoFieldByName('webform_multiple_no_add_more_input[add][more_items]', '1');

    // Check custom labels.
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-custom-label-add-submit" formnovalidate="formnovalidate" type="submit" id="edit-webform-multiple-custom-label-add-submit" name="webform_multiple_custom_label_table_add" value="{add_more_button_label}" class="button js-form-submit form-submit" />');
    $this->assertRaw('<span class="field-suffix">{add_more_input_label}</span>');

    // Check that operations is disabled.
    $this->assertNoRaw('data-drupal-selector="edit-webform-multiple-no-operations-items-0-operations-remove"');

    // Check no items message.
    $this->assertRaw('No items entered. Please add items below.');

    // Check that required does not include any empty elements.
    $this->assertFieldByName('webform_multiple_required[items][2][_item_]');
    $this->assertNoFieldByName('webform_multiple_required[items][3][_item_]');

    // Check custom label, wrapper, and element attributes.
    $this->assertRaw('<div class="custom-ajax" id="webform_multiple_custom_attributes_table">');
    $this->assertRaw('<div class="custom-table-wrapper webform-multiple-table">');
    $this->assertRaw('<table class="custom-table responsive-enabled" data-drupal-selector="edit-webform-multiple-custom-attributes-items" id="edit-webform-multiple-custom-attributes-items" data-striping="1">');
    $this->assertRaw('<th class="custom-label webform_multiple_custom_attributes-table--textfield webform-multiple-table--textfield">textfield</th>');
    $this->assertRaw('<label class="custom-label visually-hidden"');
    $this->assertRaw('<div class="custom-wrapper js-form-item form-item');
    $this->assertRaw('<input class="custom-element form-text"');

    /**************************************************************************/
    // Validation.
    /**************************************************************************/

    // Check unique #key validation.
    $edit = [
      'webform_multiple_key[items][1][value]' => 'one',
    ];
    $this->drupalPostForm('/webform/test_element_multiple', $edit, t('Submit'));
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

    // Check adding empty after one.
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_default_table_add_0');
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][1][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][3][_item_]', 'Three');

    // Check removing empty after one.
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_default_table_remove_1');
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Three');

    // Check adding 'four' and 1 more option.
    $edit = [
      'webform_multiple_default[items][3][_item_]' => 'Four',
    ];
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_default_table_add');
    $this->assertFieldByName('webform_multiple_default[items][3][_item_]', 'Four');
    $this->assertFieldByName('webform_multiple_default[items][4][_item_]', '');

    // Check add 10 more rows.
    $edit = ['webform_multiple_default[add][more_items]' => 10];
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_default_table_add');
    $this->assertFieldByName('webform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][15][_item_]', '');

    // Check remove 'one' options.
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_default_table_remove_0');
    $this->assertNoFieldByName('webform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', 'Three');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Four');

    // Add one options to 'webform_multiple_no_items'.
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_no_items_table_add');
    $this->assertNoRaw('No items entered. Please add items below.');
    $this->assertFieldByName('webform_multiple_no_items[items][0][_item_]');

    // Check no items message is never displayed when #required.
    $webform->setElementProperties('webform_multiple_no_items', ['#type' => 'webform_multiple', '#title' => 'webform_multiple_no_items', '#required' => TRUE]);
    $webform->save();
    $this->drupalGet('/webform/test_element_multiple');
    $this->assertNoRaw('No items entered. Please add items below.');
    $this->drupalPostForm(NULL, $edit, 'webform_multiple_default_table_remove_0');
    $this->assertNoRaw('No items entered. Please add items below.');
  }

}
