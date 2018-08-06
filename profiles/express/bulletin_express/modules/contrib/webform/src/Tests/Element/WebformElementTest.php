<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform elements.
 *
 * @group Webform
 */
class WebformElementTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_allowed_tags'];

  /**
   * Test element settings.
   */
  public function testElements() {

    /**************************************************************************/
    // Allowed tags
    /**************************************************************************/

    // Check <b> tags is allowed.
    $this->drupalGet('webform/test_element_allowed_tags');
    $this->assertRaw('Hello <b>...Goodbye</b>');

    // Check custom <ignored> <tag> is allowed and <b> tag removed.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('elements.allowed_tags', 'ignored tag')
      ->save();
    $this->drupalGet('webform/test_element_allowed_tags');
    $this->assertRaw('Hello <ignored></tag>...Goodbye');

    // Restore admin tags.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('elements.allowed_tags', 'admin')
      ->save();
  }

}
