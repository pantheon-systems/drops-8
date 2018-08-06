<?php

namespace Drupal\webform_ui\Tests;

use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform UI element properties.
 *
 * @group WebformUi
 */
class WebformUiElementPropertiesTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'taxonomy', 'webform', 'webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['example_layout_basic', 'example_elements', 'example_elements_states', 'example_elements_composite', 'test_element', 'test_element_access', 'test_form_states_triggers'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests element properties.
   */
  public function testElementProperties() {
    $this->drupalLogin($this->adminWebformUser);

    // Loops through all the elements, edits them via the UI, and checks that
    // the element's render array has not been altered.
    // This verifies that the edit element (via UI) form is not unexpectedly
    // altering an element's render array.
    foreach (static::$testWebforms as $webform_id) {
      /** @var \Drupal\webform\WebformInterface $webform_elements */
      $webform_elements = Webform::load($webform_id);
      $original_elements = $webform_elements->getElementsDecodedAndFlattened();
      foreach ($original_elements as $key => $original_element) {
        $this->drupalPostForm('admin/structure/webform/manage/' . $webform_elements->id() . '/element/' . $key . '/edit', [], t('Save'));

        // Must reset the webform entity cache so that the update elements can
        // be loaded.
        \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();

        /** @var \Drupal\webform\WebformInterface $webform_elements */
        $webform_elements = Webform::load($webform_id);
        $updated_element = $webform_elements->getElementsDecodedAndFlattened()[$key];

        $this->assertEqual($original_element, $updated_element, "'$key'' properties is equal.");
      }
    }
  }

}
