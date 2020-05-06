<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element multiple property.
 *
 * @group Webform
 */
class WebformElementMultiplePropertyTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_multiple_property'];

  /**
   * Tests multiple element.
   */
  public function testMultipleProperty() {
    // Check processing.
    $this->drupalPostForm('/webform/test_element_multiple_property', [], t('Submit'));
    $this->assertRaw('webform_element_multiple: false
webform_element_multiple_true: true
webform_element_multiple_false: false
webform_element_multiple_custom: 5
webform_element_multiple_disabled: 5
webform_element_multiple_true_access: true
webform_element_multiple_false_access: false
webform_element_multiple_custom_access: 5');
  }

}
