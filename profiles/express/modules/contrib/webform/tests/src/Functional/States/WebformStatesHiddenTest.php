<?php

namespace Drupal\Tests\webform\Functional\States;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform states hidden.
 *
 * @group Webform
 */
class WebformStatesHiddenTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_states_server_hidden'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'file', 'webform'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests states hidden..
   */
  public function testFormStatesHidden() {
    $this->drupalGet('/webform/test_states_server_hidden');

    // Text field.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-dependent-textfield form-item-dependent-textfield">');

    // Text field multiple.
    $this->assertRaw('<div class="js-form-wrapper js-webform-states-hidden" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-states-server-hidden-add-form :input[name=\u0022trigger_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}"><div id="dependent_textfield_multiple_table">');

    // Checkbox.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-dependent-checkbox form-item-dependent-checkbox">');

    // Radios.
    $this->assertRaw('<fieldset data-drupal-selector="edit-dependent-radios" class="js-webform-states-hidden radios--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-radios webform-type-radios js-form-item form-item js-form-wrapper form-wrapper" id="edit-dependent-radios--wrapper" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-states-server-hidden-add-form :input[name=\u0022trigger_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">');

    // Select other.
    $this->assertRaw('<fieldset data-drupal-selector="edit-dependent-select-other" class="js-webform-select-other webform-select-other js-webform-states-hidden js-form-item webform-select-other--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-webform-select-other webform-type-webform-select-other form-item js-form-wrapper form-wrapper" id="edit-dependent-select-other" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-states-server-hidden-add-form :input[name=\u0022trigger_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">');

    // Managed file.
    $this->assertRaw('<div class="js-form-wrapper js-webform-states-hidden" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-states-server-hidden-add-form :input[name=\u0022trigger_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">');

    // Address composite states wrapper.
    $this->assertRaw('<div class="js-form-wrapper js-webform-states-hidden" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-states-server-hidden-add-form :input[name=\u0022trigger_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}"><fieldset data-drupal-selector="edit-dependent-address" class="webform-address--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-webform-address webform-type-webform-address js-form-item form-item js-form-wrapper form-wrapper" id="edit-dependent-address--wrapper">');

    // Table select sort.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-webform-tableselect-sort form-type-webform-tableselect-sort js-form-item-dependent-tableselect-sort form-item-dependent-tableselect-sort form-no-label">');

    // Details.
    $this->assertRaw('<details data-webform-states-no-clear data-webform-key="dependent_details" class="js-webform-states-hidden js-form-wrapper form-wrapper" data-drupal-selector="edit-dependent-details" id="edit-dependent-details" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-states-server-hidden-add-form :input[name=\u0022trigger_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">    <summary role="button" aria-controls="edit-dependent-details" aria-expanded="false" aria-pressed="false">dependent_details</summary><div class="details-wrapper">');
  }

}
