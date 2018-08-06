<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element options.
 *
 * @group Webform
 */
class WebformElementOptionsTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_options'];

  /**
   * Tests building of options elements.
   */
  public function testWebformElementOptions() {

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_options', [], t('Submit'));
    $this->assertRaw("webform_options: {  }
webform_options_default_value:
  one: One
  two: Two
  three: Three
webform_options_optgroup:
  'Group One':
    one: One
  'Group Two':
    two: Two
  'Group Three':
    three: Three
webform_element_options_entity: yes_no
webform_element_options_custom:
  one: One
  two: Two
  three: Three");

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_options', ['webform_element_options_custom[options]' => 'yes_no'], t('Submit'));
    $this->assertRaw("webform_element_options_custom: yes_no");
  }

}
