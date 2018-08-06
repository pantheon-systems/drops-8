<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for markup element.
 *
 * @group Webform
 */
class WebformElementMarkupTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_markup'];

  /**
   * Test markup element.
   */
  public function testMarkupElement() {
    $this->drupalGet('webform/test_element_markup');

    $this->assertRaw('<p>This is normal markup</p>');
    $this->assertRaw('<p>This is only displayed on the form view.</p>');
    $this->assertNoRaw('<p>This is only displayed on the submission view.</p>');
    $this->assertRaw('<p>This is displayed on the both the form and submission view.</p>');

    $this->drupalPostForm('webform/test_element_markup', [], t('Preview'));
    $this->assertNoRaw('<p>This is normal markup</p>');
    $this->assertNoRaw('<p>This is only displayed on the form view.</p>');
    $this->assertRaw('<p>This is only displayed on the submission view.</p>');
    $this->assertRaw('<p>This is displayed on the both the form and submission view.</p>');
  }

}
