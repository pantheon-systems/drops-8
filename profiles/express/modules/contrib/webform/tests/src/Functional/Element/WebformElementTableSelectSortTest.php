<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for table select and sort elements.
 *
 * @group Webform
 */
class WebformElementTableSelectSortTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_table_select_sort'];

  /**
   * Tests table select and sort elements.
   */
  public function testTableSelectSort() {

    $webform = Webform::load('test_element_table_select_sort');

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
    $this->drupalPostForm('/webform/test_element_table_select_sort', $edit, t('Submit'));
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
    $this->drupalPostForm('/webform/test_element_table_select_sort', $edit, t('Submit'));
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
