<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for element section.
 *
 * @group Webform
 */
class WebformElementSectionTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_section'];

  /**
   * Test element section.
   */
  public function testSection() {
    $this->drupalGet('/webform/test_element_section');

    // Check section element.
    $this->assertRaw('<section data-drupal-selector="edit-webform-section" aria-describedby="edit-webform-section--description" id="edit-webform-section" class="required webform-element-help-container--title webform-element-help-container--title-after js-form-item form-item js-form-wrapper form-wrapper webform-section" required="required" aria-required="true">');
    $this->assertRaw('<h2 class="webform-section-title js-form-required form-required">webform_section<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;webform_section&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text.&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $this->assertRaw('<div class="description"><div id="edit-webform-section--description" class="webform-element-description">This is a description.</div>');
    $this->assertRaw('<div id="edit-webform-section--more" class="js-webform-element-more webform-element-more">');

    // Check custom h5 title tag.
    $this->assertRaw('<section data-drupal-selector="edit-webform-section-title-custom" id="edit-webform-section-title-custom" class="js-form-item form-item js-form-wrapper form-wrapper webform-section">');
    $this->assertRaw('<h5 style="color: red" class="webform-section-title">webform_section_title_custom</h5>');

    // Check section title_display: invisible.
    $this->assertRaw('<h2 class="visually-hidden webform-section-title">webform_section_title_invisible</h2>');

    // Check change default title tag.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_section_title_tag', 'address')
      ->save();

    $this->drupalGet('/webform/test_element_section');
    $this->assertNoRaw('<h2 class="webform-section-title js-form-required form-required">');
    $this->assertRaw('<address class="webform-section-title js-form-required form-required">');
  }

}
