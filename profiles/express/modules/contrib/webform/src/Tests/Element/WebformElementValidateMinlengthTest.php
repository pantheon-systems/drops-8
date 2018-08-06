<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform validate minlength.
 *
 * @group Webform
 */
class WebformElementValidateMinlengthTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_minlength'];

  /**
   * Tests element validate minlength.
   */
  public function testValidateMinlength() {
    $webform = Webform::load('test_element_validate_minlength');

    // Check minlength validation.
    $this->postSubmission($webform, ['minlength_textfield' => 'X']);
    $this->assertRaw('<em class="placeholder">minlength_textfield</em> cannot be less than <em class="placeholder">5</em> characters but is currently <em class="placeholder">1</em> characters long.');
  }

}
