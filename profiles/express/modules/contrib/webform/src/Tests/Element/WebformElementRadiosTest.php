<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element radioss.
 *
 * @group Webform
 */
class WebformElementRadiosTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_radios'];

  /**
   * Tests radios element.
   */
  public function testElementRadios() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('webform/test_element_radios');

    // Check radios with description display.
    $this->assertRaw('<input data-drupal-selector="edit-radios-description-one" aria-describedby="edit-radios-description-one--description" type="radio" id="edit-radios-description-one" name="radios_description" value="one" class="form-radio" />');
    $this->assertRaw('<label for="edit-radios-description-one" class="option">One</label>');
    $this->assertRaw('<div id="edit-radios-description-one--description" class="description">');

    // Check radios with help text display.
    $this->assertRaw('<input data-drupal-selector="edit-radios-help-one" type="radio" id="edit-radios-help-one" name="radios_help" value="one" class="form-radio" />');
    $this->assertRaw('<label for="edit-radios-help-one" class="option">One<a href="#help" title="This is a description" data-webform-help="This is a description" class="webform-element-help">?</a>');

    // Check radios results does not include description.
    $edit = [
      'radios_required' => 'Yes',
      'radios_required_conditional_trigger' => FALSE,
      'buttons_required_conditional_trigger' => FALSE,
      'radios_description' => 'one',
      'radios_help' => 'two',
    ];
    $this->drupalPostForm('webform/test_element_radios', $edit, t('Preview'));
    $this->assertPattern('#<label>radios_description</label>\s+One#');
    $this->assertPattern('#<label>radios_help</label>\s+Two#');
  }

}
