<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform autocomplete element.
 *
 * @group Webform
 */
class WebformElementAutocompleteTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_autocomplete'];

  /**
   * Tests autocomplete element.
   */
  public function testAutocomplete() {
    global $base_path;

    $this->drupalLogin($this->rootUser);

    /* Test #autocomplete property */

    $this->drupalGet('/webform/test_element_autocomplete');
    $this->assertRaw('<input autocomplete="off" data-drupal-selector="edit-autocomplete-off" type="email" id="edit-autocomplete-off" name="autocomplete_off" value="" size="60" maxlength="254" class="form-email" />');

    /* Test #autocomplete_items element property */

    // Check routes data-drupal-selector.
    $this->drupalGet('/webform/test_element_autocomplete');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-items" class="form-autocomplete form-text webform-autocomplete" data-autocomplete-path="' . $base_path . 'webform/test_element_autocomplete/autocomplete/autocomplete_items" type="text" id="edit-autocomplete-items" name="autocomplete_items" value="" size="60" maxlength="255" />');

    // Check #autocomplete_items partial match.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_items', ['query' => ['q' => 'United']]);
    $this->assertRaw('[{"value":"United Arab Emirates","label":"United Arab Emirates"},{"value":"United Kingdom","label":"United Kingdom"},{"value":"United States","label":"United States"}]');

    // Check #autocomplete_items exact match.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_items', ['query' => ['q' => 'United States']]);
    $this->assertRaw('[{"value":"United States","label":"United States"}]');

    // Check #autocomplete_items just one character.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_items', ['query' => ['q' => 'U']]);
    // @todo Remove once Drupal 8.8.x is only supported.
    if (floatval(\Drupal::VERSION) >= 8.8) {
      $this->assertRaw('[{"value":"Anguilla","label":"Anguilla"},{"value":"Antigua \u0026 Barbuda","label":"Antigua \u0026 Barbuda"},{"value":"Aruba","label":"Aruba"},{"value":"Australia","label":"Australia"},{"value":"Austria","label":"Austria"}]');
    }
    else {
      $this->assertRaw('[{"value":"Anguilla","label":"Anguilla"},{"value":"Antigua and Barbuda","label":"Antigua and Barbuda"},{"value":"Aruba","label":"Aruba"},{"value":"Australia","label":"Australia"},{"value":"Austria","label":"Austria"}]');
    }

    /* Test #autocomplete_existing element property */

    // Check autocomplete is not enabled until there is a submission.
    $this->drupalGet('/webform/test_element_autocomplete');
    $this->assertNoRaw('<input data-drupal-selector="edit-autocomplete-existing" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'webform/test_element_autocomplete/autocomplete/autocomplete_existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" />');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" class="form-text webform-autocomplete" />');

    // Check #autocomplete_existing no match.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'abc']]);
    $this->assertRaw('[]');

    // Add #autocomplete_existing values to the submission table.
    $this->drupalPostForm('/webform/test_element_autocomplete', ['autocomplete_existing' => 'abcdefg'], t('Submit'));

    // Check #autocomplete_existing enabled now that there is submission.
    $this->drupalGet('/webform/test_element_autocomplete');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-existing" class="form-autocomplete form-text webform-autocomplete" data-autocomplete-path="' . $base_path . 'webform/test_element_autocomplete/autocomplete/autocomplete_existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" />');
    $this->assertNoRaw('<input data-drupal-selector="edit-autocomplete-existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" class="form-text webform-autocomplete" />');

    // Check #autocomplete_existing match.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'abc']]);
    $this->assertNoRaw('[]');
    $this->assertRaw('[{"value":"abcdefg","label":"abcdefg"}]');

    // Check #autocomplete_existing minimum number of characters < 3.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'ab']]);
    $this->assertRaw('[]');
    $this->assertNoRaw('[{"value":"abcdefg","label":"abcdefg"}]');

    /* Test #autocomplete_existing and #autocomplete_items element property */

    // Add #autocomplete_body values to the submission table.
    $this->drupalPostForm('/webform/test_element_autocomplete', ['autocomplete_both' => 'Existing Item'], t('Submit'));

    // Check #autocomplete_both match.
    $this->drupalGet('/webform/test_element_autocomplete/autocomplete/autocomplete_both', ['query' => ['q' => 'Item']]);
    $this->assertNoRaw('[]');
    $this->assertRaw('[{"value":"Example Item","label":"Example Item"},{"value":"Existing Item","label":"Existing Item"}]');
  }

}
