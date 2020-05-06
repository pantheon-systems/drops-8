<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for excluded columns element.
 *
 * @group Webform
 */
class WebformElementExcludedColumnsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_excluded_columns'];

  /**
   * Test excluded columns element.
   */
  public function testExcluedElements() {
    $this->drupalGet('/webform/test_element_excluded_columns');

    $this->assertFieldByName('webform_excluded_columns[tableselect][textfield]');
    $this->assertNoFieldByName('webform_excluded_columns[tableselect][markup]');
    $this->assertNoFieldByName('webform_excluded_columns[tableselect][details]');
  }

}
