<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for element allowed tags.
 *
 * @group Webform
 */
class WebformElementAllowsTagsTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_allowed_tags'];

  /**
   * Test element allowed tags.
   */
  public function testAllowsTags() {
    // Check <b> tags is allowed.
    $this->drupalGet('webform/test_element_allowed_tags');
    $this->assertRaw('Hello <b>...Goodbye</b>');

    // Check custom <ignored> <tag> is allowed and <b> tag removed.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.allowed_tags', 'ignored tag')
      ->save();
    $this->drupalGet('webform/test_element_allowed_tags');
    $this->assertRaw('Hello <ignored></tag>...Goodbye');

    // Restore admin tags.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.allowed_tags', 'admin')
      ->save();
  }

}
