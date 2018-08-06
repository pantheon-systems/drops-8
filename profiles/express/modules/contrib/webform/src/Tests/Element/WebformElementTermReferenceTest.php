<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for term reference elements.
 *
 * @group Webform
 */
class WebformElementTermReferenceTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_term_reference'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create 'tags' vocabulary.
    $this->createTags();
  }

  /**
   * Test term reference element.
   */
  public function testTermReference() {
    $webform = Webform::load('test_element_term_reference');

    /**************************************************************************/
    // Term checkboxes
    /**************************************************************************/

    $this->drupalGet('webform/test_element_term_reference');

    // Check term checkboxes tree default.
    $this->assertRaw('<fieldset data-drupal-selector="edit-webform-term-checkboxes-tree-default" class="js-webform-term-checkboxes webform-term-checkboxes webform-term-checkboxes-scroll fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper" id="edit-webform-term-checkboxes-tree-default--wrapper">');
    $this->assertRaw('<span class="field-prefix">&nbsp;&nbsp;&nbsp;</span>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-term-checkboxes-tree-default-2" type="checkbox" id="edit-webform-term-checkboxes-tree-default-2" name="webform_term_checkboxes_tree_default[2]" value="2" class="form-checkbox" />');
    $this->assertRaw('<label for="edit-webform-term-checkboxes-tree-default-2" class="option">Parent 1: Child 1</label>');

    // Check term checkboxes tree advanced.
    $this->assertRaw('<fieldset data-drupal-selector="edit-webform-term-checkboxes-tree-advanced" class="js-webform-term-checkboxes webform-term-checkboxes fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper" id="edit-webform-term-checkboxes-tree-advanced--wrapper">');
    $this->assertRaw('<span class="field-prefix">..</span>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-term-checkboxes-tree-advanced-2" type="checkbox" id="edit-webform-term-checkboxes-tree-advanced-2" name="webform_term_checkboxes_tree_advanced[2]" value="2" class="form-checkbox" />');
    $this->assertRaw('<label for="edit-webform-term-checkboxes-tree-advanced-2" class="option">Parent 1: Child 1</label>');

    // Check term checkboxes breadcrumb.
    $this->assertRaw('<label for="edit-webform-term-checkboxes-breadcrumb-default-2" class="option">Parent 1 › Parent 1: Child 1</label>');

    // Check term checkboxes breadcrumb advanced formatting.
    $edit = [
      'webform_term_checkboxes_breadcrumb_advanced[2]' => TRUE,
      'webform_term_checkboxes_breadcrumb_advanced[3]' => TRUE,
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertRaw('<label>webform_term_checkboxes_breadcrumb_advanced</label>');
    $this->assertRaw('<div class="item-list"><ul><li>Parent 1 › Parent 1: Child 1</li><li>Parent 1 › Parent 1: Child 2</li></ul></div>');

    /**************************************************************************/
    // Term select.
    /**************************************************************************/

    $this->drupalGet('webform/test_element_term_reference');

    // Check term select tree default.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">-Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">-Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">-Parent 1: Child 3</option>');

    // Check term select tree advanced.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">..Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">..Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">..Parent 1: Child 3</option>');

    // Check term select breadcrumb default.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">Parent 1 › Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">Parent 1 › Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">Parent 1 › Parent 1: Child 3</option>');

    // Check term select breadcrumb advanced.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">Parent 1 » Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">Parent 1 » Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">Parent 1 » Parent 1: Child 3</option>');

    // Check term select breadcrumb advanced formatting.
    $edit = [
      'webform_term_select_breadcrumb_advanced[]' => [2, 3],
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertRaw('<label>webform_term_select_breadcrumb_advanced</label>');
    $this->assertRaw('<div class="item-list"><ul><li>Parent 1 › Parent 1: Child 1</li><li>Parent 1 › Parent 1: Child 2</li></ul></div>');
  }

}
