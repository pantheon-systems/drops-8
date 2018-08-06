<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for table elements.
 *
 * @group Webform
 */
class WebformElementTableTest extends WebformTestBase {

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

    $webform = Webform::load('test_element_table');

    /**************************************************************************/
    // table
    /**************************************************************************/

    // Check display elements within a table.
    $this->drupalGet('webform/test_element_table');
    $this->assertRaw('<table class="js-form-wrapper responsive-enabled" data-drupal-selector="edit-table" id="edit-table" data-striping="1">');
    $this->assertRaw('<th>First Name</th>');
    $this->assertRaw('<th>Last Name</th>');
    $this->assertRaw('<th>Gender</th>');
    $this->assertRaw('<tr data-drupal-selector="edit-table-1" class="odd">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table__1__first-name form-item-table__1__first-name form-no-label">');
    $this->assertRaw('<input data-drupal-selector="edit-table-1-first-name" type="text" id="edit-table-1-first-name" name="table__1__first_name" value="John" size="20" maxlength="255" class="form-text" />');

    // Check rendering.
    $this->drupalPostForm('webform/test_element_table', [], t('Preview'));
    $this->assertRaw('<th>First Name</th>');
    $this->assertRaw('<th>Last Name</th>');
    $this->assertRaw('<th>Gender</th>');
    $this->assertRaw('<th>Markup</th>');
    $this->assertRaw('<td>John</td>');
    $this->assertRaw('<td>Smith</td>');
    $this->assertRaw('<td>Male</td>');
    $this->assertRaw('<td>{markup_1}</td>');
    $this->assertRaw('<td>Jane</td>');
    $this->assertRaw('<td>Doe</td>');
    $this->assertRaw('<td>Female</td>');
    $this->assertRaw('<td>{markup_2}</td>');

    /**************************************************************************/
    // Table select sort.
    /**************************************************************************/

    // Check processing.
    $edit = [
      'webform_tableselect_sort_custom[one][weight]' => '4',
      'webform_tableselect_sort_custom[two][weight]' => '3',
      'webform_tableselect_sort_custom[three][weight]' => '2',
      'webform_tableselect_sort_custom[four][weight]' => '1',
      'webform_tableselect_sort_custom[five][weight]' => '0',
      'webform_tableselect_sort_custom[one][checkbox]' => TRUE,
      'webform_tableselect_sort_custom[two][checkbox]' => TRUE,
      'webform_tableselect_sort_custom[three][checkbox]' => TRUE,
      'webform_tableselect_sort_custom[four][checkbox]' => TRUE,
      'webform_tableselect_sort_custom[five][checkbox]' => TRUE,
    ];
    $this->drupalPostForm('webform/test_element_table', $edit, t('Submit'));
    $this->assertRaw("webform_tableselect_sort_custom:
  - five
  - four
  - three
  - two
  - one");

    /**************************************************************************/
    // Table sort.
    /**************************************************************************/

    // Check processing.
    $edit = [
      'webform_table_sort_custom[one][weight]' => '4',
      'webform_table_sort_custom[two][weight]' => '3',
      'webform_table_sort_custom[three][weight]' => '2',
      'webform_table_sort_custom[four][weight]' => '1',
      'webform_table_sort_custom[five][weight]' => '0',
    ];
    $this->drupalPostForm('webform/test_element_table', $edit, t('Submit'));
    $this->assertRaw("webform_table_sort_custom:
  - five
  - four
  - three
  - two
  - one");

    /**************************************************************************/
    // Export results.
    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    $excluded_columns = $this->getExportColumns($webform);
    unset($excluded_columns['webform_tableselect_sort_custom']);

    $this->getExport($webform, ['options_single_format' => 'separate', 'options_multiple_format' => 'separate', 'excluded_columns' => $excluded_columns]);
    $this->assertRaw('"webform_tableselect_sort (custom): one","webform_tableselect_sort (custom): two","webform_tableselect_sort (custom): three","webform_tableselect_sort (custom): four","webform_tableselect_sort (custom): five"');
    $this->assertRaw('5,4,3,2,1');
  }

}
