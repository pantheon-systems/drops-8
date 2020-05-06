<?php

namespace Drupal\Tests\webform_jqueryui_buttons\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for webform element buttons.
 *
 * @group Webform
 */
class WebformElementButtonsTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_jqueryui_buttons', 'webform_jqueryui_buttons_test'];

  /**
   * Tests buttons elements.
   */
  public function testBuildingOtherElements() {
    $this->drupalGet('/webform/test_element_buttons');

    // Check basic buttons_other.
    $this->assertRaw('<span class="fieldset-legend">buttons_other_basic</span>');
    $this->assertRaw('<input data-drupal-selector="edit-buttons-other-basic-buttons-one" type="radio" id="edit-buttons-other-basic-buttons-one" name="buttons_other_basic[buttons]" value="One" class="form-radio" />');
    $this->assertRaw('<label for="edit-buttons-other-basic-buttons-one" class="option">One</label>');
    $this->assertRaw('<input data-drupal-selector="edit-buttons-other-basic-other" type="text" id="edit-buttons-other-basic-other" name="buttons_other_basic[other]" value="Four" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    // Check advanced buttons_other w/ custom label.
    $this->assertRaw('<span class="fieldset-legend js-form-required form-required">buttons_other_advanced</span>');
    $this->assertRaw('<input data-drupal-selector="edit-buttons-other-advanced-buttons-one" type="radio" id="edit-buttons-other-advanced-buttons-one" name="buttons_other_advanced[buttons]" value="One" class="form-radio" />');
    $this->assertRaw('<input data-drupal-selector="edit-buttons-other-advanced-other" aria-describedby="edit-buttons-other-advanced-other--description" type="text" id="edit-buttons-other-advanced-other" name="buttons_other_advanced[other]" value="Four" size="60" maxlength="255" placeholder="What is this other option" class="form-text" />');
    $this->assertRaw('<div id="edit-buttons-other-advanced-other--description" class="webform-element-description">Other button description</div>');

    // Check buttons other required when checked.
    $edit = [
      'buttons_other_basic[buttons]' => '_other_',
      'buttons_other_basic[other]' => '',
    ];
    $this->drupalPostForm('/webform/test_element_buttons', $edit, t('Submit'));
    $this->assertRaw('buttons_other_basic field is required.');

    // Check buttons other not required when not checked.
    $edit = [
      'buttons_other_basic[buttons]' => 'One',
      'buttons_other_basic[other]' => '',
    ];
    $this->drupalPostForm('/webform/test_element_buttons', $edit, t('Submit'));
    $this->assertNoRaw('buttons_other_basic field is required.');

    // Check buttons other required validation.
    $edit = [
      'buttons_other_advanced[buttons]' => '_other_',
      'buttons_other_advanced[other]' => '',
    ];
    $this->drupalPostForm('/webform/test_element_buttons', $edit, t('Submit'));
    $this->assertRaw('buttons_other_advanced field is required.');

    // Check buttons other processing w/ other.
    $edit = [
      'buttons_other_advanced[buttons]' => '_other_',
      'buttons_other_advanced[other]' => 'Five',
    ];
    $this->drupalPostForm('/webform/test_element_buttons', $edit, t('Submit'));
    $this->assertRaw('buttons_other_advanced: Five');

    // Check buttons other processing w/o other.
    $edit = [
      'buttons_other_advanced[buttons]' => 'One',
      // This value is ignored, because 'buttons_other_advanced[buttons]' is not set to '_other_'.
      'buttons_other_advanced[other]' => 'Five',
    ];
    $this->drupalPostForm('/webform/test_element_buttons', $edit, t('Submit'));
    $this->assertRaw('buttons_other_advanced: One');
    $this->assertNoRaw('buttons_other_advanced: Five');
  }

}
