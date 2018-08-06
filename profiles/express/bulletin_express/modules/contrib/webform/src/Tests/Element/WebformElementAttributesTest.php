<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element attributes.
 *
 * @group Webform
 */
class WebformElementAttributesTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_attributes'];

  /**
   * Tests element attributes.
   */
  public function testWebformElementAttributes() {
    // Check default value handling.
    $this->drupalPostForm('webform/test_element_attributes', [], t('Submit'));
    $this->assertRaw("webform_element_attributes:
  class:
    - one
    - two
    - four
  style: 'color: red'
  custom: test");
  }

}
