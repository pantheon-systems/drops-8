<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for table elements.
 *
 * @group Webform
 */
class WebformElementTableTest extends WebformElementBrowserTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_ui', 'file'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_table'];

  /**
   * Tests table elements.
   */
  public function testTable() {
    global $base_path;

    $webform = Webform::load('test_element_table');

    $this->drupalGet('/webform/test_element_table');

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    // Check default table rendering.
    $this->assertRaw('<table class="js-form-wrapper responsive-enabled" data-drupal-selector="edit-table" id="edit-table" data-striping="1">');
    $this->assertPattern('#<th>First Name</th>\s+<th>Last Name</th>\s+<th>Gender</th>#');
    $this->assertRaw('<tr data-drupal-selector="edit-table-1" class="odd">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table__1__first-name form-item-table__1__first-name form-no-label">');
    $this->assertRaw('<input data-drupal-selector="edit-table-1-first-name" type="text" id="edit-table-1-first-name" name="table__1__first_name" value="John" size="20" maxlength="255" class="form-text" />');

    // Check webform table basic rendering.
    $this->assertRaw('<table data-drupal-selector="edit-table-basic" class="webform-table responsive-enabled" id="edit-table-basic" data-striping="1">');
    $this->assertRaw('<tr data-drupal-selector="edit-table-basic-01" class="webform-table-row odd">');
    $this->assertRaw('<input data-drupal-selector="edit-table-basic-01-first-name" type="text" id="edit-table-basic-01-first-name" name="table_basic_01_first_name" value="" size="60" maxlength="255" class="form-text" />');

    // Check webform table advanced rendering.
    $this->assertRaw('<table data-drupal-selector="edit-table-advanced" class="webform-table sticky-enabled responsive-enabled" id="edit-table-advanced" data-striping="1">');
    $this->assertPattern('#<th width="50%">Composite</th>\s+<th width="50%">Nested</th>#');

    // Check webform table states rendering.
    $this->assertRaw('<table data-drupal-selector="edit-table-states" class="webform-table responsive-enabled" id="edit-table-states" data-drupal-states="{&quot;invisible&quot;:{&quot;.webform-submission-test-element-table-add-form :input[name=\u0022table_rows\u0022]&quot;:{&quot;value&quot;:&quot;&quot;}}}" data-striping="1">');
    $this->assertRaw('<tr data-drupal-selector="edit-table-states-01" class="webform-table-row js-form-item odd" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-element-table-add-form :input[name=\u0022table_rows\u0022]&quot;:{&quot;value&quot;:{&quot;greater&quot;:&quot;0&quot;}}}}">');

    /**************************************************************************/
    // Display.
    /**************************************************************************/

    $edit = [
      'table_basic_01_first_name' => 'Ringo',
      'table_basic_01_last_name' => 'Starr',
      'table_basic_01_gender' => 'Male',
      'table_advanced_01_first_name' => 'John',
      'table_advanced_01_last_name' => 'Lennon',
      'table_advanced_01_gender' => 'Male',
    ];
    $this->drupalPostForm('/webform/test_element_table', $edit, t('Preview'));

    // Check data.
    $this->assertRaw("table__1__first_name: John
table__1__last_name: Smith
table__1__gender: Male
table__2__first_name: Jane
table__2__last_name: Doe
table__2__gender: Female
table_basic_01_first_name: Ringo
table_basic_01_last_name: Starr
table_basic_01_gender: Male
table_advanced_01_address: null
table_advanced_01_first_name: John
table_advanced_01_last_name: Lennon
table_advanced_01_gender: Male
table_advanced_01_managed_file: null
table_rows: '1'
table_advanced_01_textfield: ''
table_advanced_02_textfield: ''
table_advanced_03_textfield: ''
table_advanced_04_textfield: ''");

    // Check default table display.
    $this->assertPattern('#<th>First Name</th>\s+<th>Last Name</th>\s+<th>Gender</th>\s+<th>Markup</th>#');
    $this->assertPattern('#<td>John</td>\s+<td>Smith</td>\s+<td>Male</td>\s+<td>{markup_1}</td>#');
    $this->assertPattern('#<td>Jane</td>\s+<td>Doe</td>\s+<td>Female</td>\s+<td>{markup_2}</td>#');

    // Check basic table display.
    $this->assertPattern('#<label>table_basic</label>\s+<table class="responsive-enabled" data-striping="1">#');
    $this->assertPattern('#<tr class="odd">\s+<td>Ringo</td>\s+<td>Starr</td>\s+<td>Male</td>\s+<td>{markup_1}</td>\s+</tr>#');

    // Check advanced table display.
    $this->assertPattern('#<label>table_advanced</label>\s+<div><details class="webform-container webform-container-type-details#');
    $this->assertPattern('<section class="js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_element_table--table_advanced_01_container">');

    // Check states table display.
    $this->assertPattern('<div class="webform-element webform-element-type-webform-table js-form-item form-item js-form-type-item form-type-item js-form-item-table-states form-item-table-states" id="test_element_table--table_states">');

    /**************************************************************************/
    // User interface.
    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    // Check that add table row is not displayed in select element.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add');
    $this->assertNoRaw('Table row');

    // Check that add table row link is displayed.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table');
    $this->assertLinkByHref("${base_path}admin/structure/webform/manage/test_element_table/element/add/webform_table_row?parent=table_basic");

    // Check that add table row without a parent table returns a 404 error.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row');
    $this->assertResponse(404);

    // Check default table row element key and title.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_basic']]);
    $this->assertFieldByName('properties[title]', 'Basic Person (2)');
    $this->assertFieldByName('key', 'table_basic_02');

    // Check table row element can duplicate sub elements from the
    // first table row.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_basic']]);
    $this->assertFieldByName('properties[duplicate]', TRUE);

    // Check table row element sub elements are duplicated.
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', [], t('Save'), ['query' => ['parent' => 'table_basic']]);
    $this->assertRaw('>table_basic_02<');
    $this->assertRaw('>table_basic_02_first_name<');
    $this->assertRaw('>table_basic_02_last_name<');
    $this->assertRaw('>table_basic_02_gender<');
    $this->assertRaw('>table_basic_02_markup<');

    // Check table row element sub elements are NOT duplicated.
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['properties[duplicate]' => FALSE], t('Save'), ['query' => ['parent' => 'table_basic']]);
    $this->assertRaw('>table_basic_03<');
    $this->assertNoRaw('>table_basic_03_first_name<');
    $this->assertNoRaw('>table_basic_03_last_name<');
    $this->assertNoRaw('>table_basic_03_gender<');
    $this->assertNoRaw('>table_basic_03_markup<');

    // Check default table row element key and title.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/textfield', ['query' => ['parent' => 'table_basic_01']]);
    $this->assertRaw("Element keys are automatically prefixed with parent row's key.");
    $this->assertRaw('<span class="field-prefix">table_basic_01_</span>');

    // Check that elements are prefixed with row key.
    $edit = [
      'key' => 'testing',
      'properties[title]' => 'Testing',
    ];
    $options = ['query' => ['parent' => 'table_basic_01']];
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_table/element/add/textfield', $edit, t('Save'), $options);
    $this->assertRaw('>table_basic_01_testing<');

    // Check table row element can NOT duplicate sub elements from the
    // first table row.
    $webform->setElementProperties('textfield', [
      '#type' => 'textfield',
      'title' => 'textfield',
    ], 'table_basic_01')->save();
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_basic']]);
    $this->assertNoFieldByName('properties[duplicate]');

    // Check prefix children disabled for table row.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_prefix_children_false']]);
    $this->assertFieldByName('properties[title]', '');
    $this->assertFieldByName('key', '');

    // Check prefix children disabled for table row element.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/textfield', ['query' => ['parent' => 'table_prefix_children_false_01']]);
    $this->assertNoRaw("Element keys are automatically prefixed with parent row's key.");
    $this->assertNoRaw('<span class="field-prefix">table_basic_01_</span>');
  }

}
