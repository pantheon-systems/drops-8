<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for entity automcomplete element.
 *
 * @group Webform
 */
class WebformElementEntityAutocompleteTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'user', 'node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_entity_autocomplete'];

  /**
   * Test entity reference elements.
   */
  public function testEntityReferenceTest() {
    $webform = Webform::load('test_element_entity_autocomplete');

    // Check render entity_autocomplete.
    $this->drupalGet('/webform/test_element_entity_autocomplete');
    $this->assertFieldByName('entity_autocomplete_user_default', 'admin (1)');
    $this->assertFieldByName('entity_autocomplete_user_tags', 'admin (1)');

    // Check process entity_autocomplete.
    $this->postSubmission($webform);
    $this->assertRaw("entity_autocomplete_user_default: '1'
entity_autocomplete_user_tags:
  - '1'
entity_autocomplete_user_multiple:
  - '1'
entity_autocomplete_node_default: null
entity_autocomplete_node_view: null");

  }

}
