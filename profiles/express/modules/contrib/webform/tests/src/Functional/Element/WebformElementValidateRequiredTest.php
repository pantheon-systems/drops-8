<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform required validation.
 *
 * @group Webform
 */
class WebformElementValidateRequiredTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_required'];

  /**
   * Tests pattern validation.
   */
  public function testPattern() {
    // Check that HTML tags are stripped  from required error attribute.
    $this->drupalGet('/webform/test_element_validate_required');
    $this->assertRaw(' <input data-webform-required-error="This is a custom required message" data-drupal-selector="edit-required-textfield-html" type="text" id="edit-required-textfield-html" name="required_textfield_html" value="" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check that HTML tags are rendered in validation message.
    $this->drupalPostForm('/webform/test_element_validate_required', [], t('Submit'));
    $this->assertRaw('<li class="messages__item">This is a <em>custom required message</em></li>');
  }

}
