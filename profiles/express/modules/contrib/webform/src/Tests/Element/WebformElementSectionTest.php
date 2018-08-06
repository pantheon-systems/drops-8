<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for element section.
 *
 * @group Webform
 */
class WebformElementSectionTest extends WebformTestBase {

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
    $this->drupalGet('webform/test_element_section');

    // Check section element.
    $this->assertRaw('<section data-drupal-selector="edit-webform-section" aria-describedby="edit-webform-section--description" id="edit-webform-section" class="js-form-item form-item js-form-wrapper form-wrapper webform-section">');

    // Check section help.
    $this->assertRaw('<h2 class="webform-section-title">webform_section<a href="#help" title="{This is help text}" data-webform-help="{This is help text}" class="webform-element-help">?</a>');

    // Check section description.
    $this->assertRaw('<div id="edit-webform-section--description" class="description">{This is a description}</div>');

    // Check section required.
    $this->assertRaw('<section data-drupal-selector="edit-webform-section-required" id="edit-webform-section-required" class="required js-form-item form-item js-form-wrapper form-wrapper webform-section" required="required" aria-required="true">');
    $this->assertRaw('<h2 class="webform-section-title js-form-required form-required">webform_section_required</h2>');

    // Check custom h5 title tag.
    $this->assertRaw('<section data-drupal-selector="edit-webform-section-title-custom" id="edit-webform-section-title-custom" class="js-form-item form-item js-form-wrapper form-wrapper webform-section">');
    $this->assertRaw('<h5 style="color: red" class="webform-section-title">webform_section_title_custom</h5>');

    // Check change default title tag.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_section_title_tag', 'address')
      ->save();

    $this->drupalGet('webform/test_element_section');
    $this->assertNoRaw('<h2 class="webform-section-title">');
    $this->assertRaw('<address class="webform-section-title">');
  }

}


