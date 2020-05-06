<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for excluded elements element.
 *
 * @group Webform
 */
class WebformElementExcludedElementsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_excluded_elements'];

  /**
   * Test excluded elements element.
   */
  public function testExcluedElements() {
    $this->drupalGet('/webform/test_element_excluded_elements');

    // Check markup is not listed via '#exclude_markup': TRUE.
    $this->assertNoFieldByName('webform_excluded_elements[tableselect][markup]');

    // Check markup is listed via '#exclude_markup': FALSE.
    $this->assertFieldByName('webform_excluded_elements_markup[tableselect][markup]');
  }

}
