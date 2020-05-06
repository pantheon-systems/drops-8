<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform validate minlength.
 *
 * @group Webform
 */
class WebformElementValidateMinlengthTest extends WebformElementBrowserTestBase {

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

    // Check minlength not required validation.
    $this->postSubmission($webform, ['minlength_textfield' => '']);
    $this->assertNoRaw('<em class="placeholder">minlength_textfield</em> cannot be less than <em class="placeholder">5</em> characters but is currently <em class="placeholder">0</em> characters long.');

    // Check minlength required validation.
    $this->postSubmission($webform, ['minlength_textfield_required' => '']);
    $this->assertNoRaw('<em class="placeholder">minlength_textfield_required</em> cannot be less than <em class="placeholder">5</em> characters but is currently <em class="placeholder">0</em> characters long.');
    $this->assertRaw('minlength_textfield_required field is required.');
  }

}
