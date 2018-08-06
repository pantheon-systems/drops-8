<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for entity reference elements.
 *
 * @group Webform
 */
class WebformElementEntityReferenceTest extends WebformTestBase {

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
  protected static $testWebforms = ['test_element_entity_reference'];

  /**
   * Test entity reference elements.
   */
  public function testWebformElementEntityReferenceTest() {
    $webform = Webform::load('test_element_entity_reference');

    // Check render entity_autocomplete.
    $this->drupalGet('webform/test_element_entity_reference');
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
entity_autocomplete_node_view: null
webform_entity_select_user_default: '1'
webform_entity_select_user_multiple:
  - '1'
webform_entity_radios_user_default: '1'
webform_entity_checkboxes_user_default:
  - '1'");

  }

}
