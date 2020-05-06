<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for markup element.
 *
 * @group Webform
 */
class WebformElementMarkupTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_markup'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_markup'];

  /**
   * Test markup element.
   */
  public function testMarkup() {

    // Check markup display on form.
    $this->drupalGet('/webform/test_element_markup');
    $this->assertRaw('<div id="edit-markup" class="js-form-item form-item js-form-type-webform-markup form-type-webform-markup js-form-item-markup form-item-markup form-no-label">');
    $this->assertRaw('<p>This is normal markup</p>');
    $this->assertRaw('<p>This is only displayed on the form view.</p>');
    $this->assertNoRaw('<p>This is only displayed on the submission view.</p>');
    $this->assertRaw('<p>This is displayed on the both the form and submission view.</p>');
    $this->assertRaw('<p>This is displayed on the both the form and submission view.</p>');

    // Check markup alter via preprocessing.
    // @see webform_test_markup_preprocess_webform_html_editor_markup()
    $this->drupalGet('/webform/test_element_markup');
    $this->assertNoRaw('<p>Alter this markup.</p>');
    $this->assertRaw('<p><em>Alter this markup.</em> <strong>This markup was altered.</strong></p>');

    // Check markup display on view.
    $this->drupalPostForm('/webform/test_element_markup', [], t('Preview'));
    $this->assertNoRaw('<p>This is normal markup</p>');
    $this->assertNoRaw('<p>This is only displayed on the form view.</p>');
    $this->assertRaw('<p>This is only displayed on the submission view.</p>');
    $this->assertRaw('<p>This is displayed on the both the form and submission view.</p>');
  }

}
