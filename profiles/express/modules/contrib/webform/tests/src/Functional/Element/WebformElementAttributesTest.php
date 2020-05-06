<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element attributes.
 *
 * @group Webform
 */
class WebformElementAttributesTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_attributes'];

  /**
   * Tests element attributes.
   */
  public function testAttributes() {
    // Check default value handling.
    $this->drupalPostForm('/webform/test_element_attributes', [], t('Submit'));
    $this->assertRaw("webform_element_attributes:
  class:
    - one
    - two
    - four
  style: 'color: red'
  custom: test");
  }

}
