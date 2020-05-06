<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform pattern validation.
 *
 * @group Webform
 */
class WebformElementValidatePatternTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_pattern'];

  /**
   * Tests pattern validation.
   */
  public function testPattern() {
    // Check rendering.
    $this->drupalGet('/webform/test_element_validate_pattern');
    $this->assertRaw('<input pattern="Hello" data-drupal-selector="edit-pattern" aria-describedby="edit-pattern--description" type="text" id="edit-pattern" name="pattern" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<input pattern="Hello" data-webform-pattern-error="You did not enter &#039;Hello&#039;" data-drupal-selector="edit-pattern-error" aria-describedby="edit-pattern-error--description" type="text" id="edit-pattern-error" name="pattern_error" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<input pattern="Hello" data-webform-pattern-error="You did not enter Hello" data-drupal-selector="edit-pattern-error-html" aria-describedby="edit-pattern-error-html--description" type="text" id="edit-pattern-error-html" name="pattern_error_html" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<input pattern="\u2E8F" data-drupal-selector="edit-pattern-unicode" aria-describedby="edit-pattern-unicode--description" type="text" id="edit-pattern-unicode" name="pattern_unicode" value="" size="60" maxlength="255" class="form-text" />');

    // Check validation.
    $edit = [
      'pattern' => 'GoodBye',
      'pattern_error' => 'GoodBye',
      'pattern_error_html' => 'GoodBye',
      'pattern_unicode' => 'Unicode',
    ];
    $this->drupalPostForm('/webform/test_element_validate_pattern', $edit, t('Submit'));
    $this->assertRaw('<li class="messages__item"><em class="placeholder">pattern</em> field is not in the right format.</li>');
    $this->assertRaw('<li class="messages__item">You did not enter &#039;Hello&#039;</li>');
    $this->assertRaw('<li class="messages__item">You did not enter <strong>Hello</strong></li>');
    $this->assertRaw('<li class="messages__item"><em class="placeholder">pattern_unicode</em> field is not in the right format.</li>');

    // Check validation.
    $edit = [
      'pattern' => 'Hello',
      'pattern_error' => 'Hello',
      'pattern_error_html' => 'Hello',
      'pattern_unicode' => '⺏',
    ];
    $this->drupalPostForm('/webform/test_element_validate_pattern', $edit, t('Submit'));
    $this->assertNoRaw('<li class="messages__item"><em class="placeholder">pattern</em> field is not in the right format.</li>');
    $this->assertNoRaw('<li class="messages__item">You did not enter &#039;Hello&#039;</li>');
    $this->assertNoRaw('<li class="messages__item">You did not enter <strong>Hello</strong></li>');
    $this->assertNoRaw('<li class="messages__item"><em class="placeholder">pattern_unicode</em> field is not in the right format.</li>');
  }

}
