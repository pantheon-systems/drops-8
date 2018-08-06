<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform text elements.
 *
 * @group Webform
 */
class WebformElementTextTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_text'];

  /**
   * Tests text elements.
   */
  public function testTextElements() {

    /**************************************************************************/
    // text format
    /**************************************************************************/

    // Check that formats and tips are removed and/or hidden.
    $this->drupalGet('webform/test_element_text');
    $this->assertRaw('<div class="filter-wrapper js-form-wrapper form-wrapper" data-drupal-selector="edit-text-format-format" style="display: none" id="edit-text-format-format">');
    $this->assertRaw('<div class="filter-help js-form-wrapper form-wrapper" data-drupal-selector="edit-text-format-format-help" style="display: none" id="edit-text-format-format-help">');

    /**************************************************************************/
    // counter
    /**************************************************************************/

    // Check counters.
    $this->drupalGet('webform/test_element_text');
    $this->assertRaw('<input data-counter-type="character" data-counter-limit="10" class="js-webform-counter webform-counter form-text" data-drupal-selector="edit-counter-characters" type="text" id="edit-counter-characters" name="counter_characters" value="" size="60" maxlength="10" />');
    $this->assertRaw('<textarea data-counter-type="word" data-counter-limit="3" data-counter-message="word(s) left. This is a custom message" class="js-webform-counter webform-counter form-textarea resize-vertical" data-drupal-selector="edit-counter-words" id="edit-counter-words" name="counter_words" rows="5" cols="60"></textarea>');

    // Check counter validation error.
    $edit = [
      'counter_characters' => '01234567890',
      'counter_words' => 'one two three four',
    ];
    $this->drupalPostForm('webform/test_element_text', $edit, t('Submit'));
    $this->assertRaw('Character counter cannot be longer than <em class="placeholder">10</em> characters but is currently <em class="placeholder">11</em> characters long.</li>');
    $this->assertRaw('Word counter cannot be longer than <em class="placeholder">3</em> words but is currently <em class="placeholder">4</em> words long.');

    // Check counter validation passes.
    $edit = [
      'counter_characters' => '0123456789',
      'counter_words' => 'one two three',
    ];
    $this->drupalPostForm('webform/test_element_text', $edit, t('Submit'));
    $this->assertNoRaw('Character counter cannot be longer than <em class="placeholder">10</em> characters but is currently <em class="placeholder">11</em> characters long.</li>');
    $this->assertNoRaw('Word counter cannot be longer than <em class="placeholder">3</em> words but is currently <em class="placeholder">4</em> words long.');

    /**************************************************************************/
    // creditcard_number
    /**************************************************************************/

    // Check basic creditcard_number.
    $this->drupalGet('webform/test_element_text');
    $this->assertRaw('<label for="edit-creditcard-number-basic">Credit card number basic</label>');
    $this->assertRaw('<input data-drupal-selector="edit-creditcard-number-basic" type="text" id="edit-creditcard-number-basic" name="creditcard_number_basic" value="" size="16" maxlength="16" class="form-text webform-creditcard-number" />');

    // Check invalid credit card number.
    $edit = [
      'creditcard_number_basic' => 'not value',
    ];
    $this->drupalPostForm('webform/test_element_text', $edit, t('Submit'));
    $this->assertRaw('The credit card number is not valid.');

    // Check valid credit card number.
    $edit = [
      'creditcard_number_basic' => '4111111111111111',
    ];
    $this->drupalPostForm('webform/test_element_text', $edit, t('Submit'));
    $this->assertNoRaw('The credit card number is not valid.');

    // Check valid AmEx (15 digit).
    $edit = [
      'creditcard_number_basic' => '378282246310005',
    ];
    $this->drupalPostForm('webform/test_element_text', $edit, t('Submit'));
    $this->assertNoRaw('The credit card number is not valid.');
  }

}
