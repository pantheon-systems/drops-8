<?php

namespace Drupal\inline_entity_form\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\NodeInterface;

/**
 * Tests the IEF simple widget.
 *
 * @group inline_entity_form
 */
class SimpleWidgetWebTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['inline_entity_form_test'];

  /**
   * Prepares environment for
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_simple_single content',
      'create ief_test_custom content',
      'edit any ief_simple_single content',
      'edit own ief_test_custom content',
      'view own unpublished content',
      'create ief_simple_entity_no_bundle content',
      'administer entity_test__without_bundle content',
    ]);
  }

  /**
   * Tests simple IEF widget with different cardinality options.
   *
   * @throws \Exception
   */
  protected function testSimpleCardinalityOptions() {
    $this->drupalLogin($this->user);
    $cardinality_options = [
      1 => 1,
      2 => 2,
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => 3,
    ];
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $this->fieldStorageConfigStorage->load('node.single');
    foreach ($cardinality_options as $cardinality => $limit) {
      $field_storage->setCardinality($cardinality);
      $field_storage->save();

      $this->drupalGet('node/add/ief_simple_single');

      $this->assertText('Single node', 'Inline entity field widget title found.');
      $this->assertText('Reference a single node.', 'Inline entity field description found.');

      $add_more_xpath = '//input[@data-drupal-selector="edit-single-add-more"]';
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        $this->assertFieldByXPath($add_more_xpath, NULL, 'Add more button exists');
      }
      else {
        $this->assertNoFieldByXPath($add_more_xpath, NULL, 'Add more button does NOT exist');
      }

      $host_title = 'Host node cardinality: ' . $cardinality;
      $edit = ['title[0][value]' => $host_title];
      for ($item_number = 0; $item_number < $limit; $item_number++) {
        $edit["single[$item_number][inline_entity_form][title][0][value]"] = 'Child node nr.' . $item_number;
        if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
          $next_item_number = $item_number + 1;
          $this->assertNoFieldByName("single[$next_item_number][inline_entity_form][title][0][value]", NULL, "Item $next_item_number does not appear before 'Add More' clicked");
          if ($item_number < $limit - 1) {
            $this->drupalPostAjaxForm(NULL, $edit, 'single_add_more');
            $this->assertFieldByName("single[$next_item_number][inline_entity_form][title][0][value]", NULL, "Item $next_item_number does  appear after 'Add More' clicked");
            // Make sure only 1 item is added.
            $unexpected_item_number = $next_item_number + 1;
            $this->assertNoFieldByName("single[$unexpected_item_number][inline_entity_form][title][0][value]", NULL, "Extra Item $unexpected_item_number is not added after 'Add More' clicked");
          }
        }
      }
      $this->drupalPostForm(NULL, $edit, t('Save'));

      for ($item_number = 0; $item_number < $limit; $item_number++) {
        $this->assertText('Child node nr.' . $item_number, 'Label of referenced entity found.');
      }

      $host_node = $this->getNodeByTitle($host_title);
      $this->checkEditAccess($host_node, $limit, $cardinality);
    }
  }

  /**
   * Test Validation on Simple Widget.
   *
   * @throws \Exception
   */
  protected function testSimpleValidation() {
    $this->drupalLogin($this->user);
    $host_node_title = 'Host Validation Node';
    $this->drupalGet('node/add/ief_simple_single');

    $this->assertText('Single node', 'Inline entity field widget title found.');
    $this->assertText('Reference a single node.', 'Inline entity field description found.');
    $this->assertText('Positive int', 'Positive int field found.');

    $edit = ['title[0][value]' => $host_node_title];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertText('Title field is required.', 'Title validation fires on Inline Entity Form widget.');
    $this->assertUrl('node/add/ief_simple_single', [], 'On add page after validation error.');

    $child_title = 'Child node ' . $this->randomString();
    $edit['single[0][inline_entity_form][title][0][value]'] = $child_title;
    $edit['single[0][inline_entity_form][positive_int][0][value]'] = -1;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoText('Title field is required.', 'Title validation passes on Inline Entity Form widget.');
    $this->assertText('Positive int must be higher than or equal to 1', 'Field validation fires on Inline Entity Form widget.');
    $this->assertUrl('node/add/ief_simple_single', [], 'On add page after validation error.');

    $edit['single[0][inline_entity_form][positive_int][0][value]'] = 1;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoText('Title field is required.', 'Title validation passes on Inline Entity Form widget.');
    $this->assertNoText('Positive int must be higher than or equal to 1', 'Field validation fires on Inline Entity Form widget.');

    // Check that nodes were created correctly.
    $host_node = $this->getNodeByTitle($host_node_title);
    if ($this->assertNotNull($host_node, 'Host node created.')) {
      $this->assertUrl('node/' . $host_node->id(), [], 'On node view page after node add.');
      $child_node = $this->getNodeByTitle($child_title);
      if ($this->assertNotNull($child_node)) {
        $this->assertEqual($host_node->single[0]->target_id, $child_node->id(), 'Child node is referenced');
        $this->assertEqual($child_node->positive_int[0]->value,1, 'Child node int field correct.');
        $this->assertEqual($child_node->bundle(),'ief_test_custom', 'Child node is correct bundle.');
      }
    }
  }

  /**
   * Tests if the entity create access works in simple widget.
   */
  public function testSimpleCreateAccess() {
    // Create a user who does not have access to create ief_test_custom nodes.
    $this->user = $this->createUser([
      'create ief_simple_single content',
    ]);
    $this->drupalLogin($this->user);
    $this->drupalGet('node/add/ief_simple_single');
    $this->assertNoFieldByName('single[0][inline_entity_form][title][0][value]', NULL);
  }

  /**
   * Tests that user only has access to the their own nodes.
   *
   * @param \Drupal\node\Entity\Node $host_node
   *   The node of the type of ief_simple_single
   * @param int $child_count
   *   The number of entity reference values in the "single" field.
   */
  protected function checkEditAccess(NodeInterface $host_node, $child_count, $cardinality) {
    $other_user = $this->createUser([
      'edit own ief_test_custom content',
      'edit any ief_simple_single content',
    ]);
    /** @var  \Drupal\node\Entity\Node $first_child_node */
    $first_child_node = $host_node->single[0]->entity;
    $first_child_node->setOwner($other_user);
    $first_child_node->save();
    $this->drupalGet("node/{$host_node->id()}/edit");
    $this->assertText($first_child_node->label());
    $this->assertNoFieldByName('single[0][inline_entity_form][title][0][value]', NULL, 'Form of child node with no edit access is not found.');
    // Check that the forms for other child nodes(if any) appear on the form.
    $delta = 1;
    while ($delta < $child_count) {
      /** @var \Drupal\node\Entity\Node $child_node */
      $child_node = $host_node->single[$delta]->entity;
      $this->assertFieldByName("single[$delta][inline_entity_form][title][0][value]", $child_node->label(), 'Form of child node with edit access is found.');
      $delta++;
    }
    // Check that there is NOT an extra "add" form when editing.
    $unexpected_item_number = $child_count;
    $this->assertNoFieldByName("single[$unexpected_item_number][inline_entity_form][title][0][value]", NULL, 'No empty "add" entity form is found on edit.');
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $next_item_number = $child_count;
      $this->drupalPostAjaxForm(NULL, [], 'single_add_more');
      $this->assertFieldByName("single[$next_item_number][inline_entity_form][title][0][value]", NULL, "Item $next_item_number does appear after 'Add More' clicked");
      // Make sure only 1 item is added.
      $unexpected_item_number = $next_item_number + 1;
      $this->assertNoFieldByName("single[$unexpected_item_number][inline_entity_form][title][0][value]", NULL, "Extra Item $unexpected_item_number is not added after 'Add More' clicked");
    }

    // Now that we have confirmed the correct fields appear, lets update the
    // values and save them. We do not have access to form for delta 0 because
    // it is owned by another user.
    $delta = 1;
    $new_titles = [];
    $edit = [];
    // Loop through an update all child node titles.
    while ($delta < $child_count) {
      /** @var \Drupal\node\Entity\Node $child_node */
      $child_node = $host_node->single[$delta]->entity;
      $new_titles[$delta] = $child_node->label() . ' - updated';
      $edit["single[$delta][inline_entity_form][title][0][value]"] = $new_titles[$delta];
      $delta++;
    }
    // If CARDINALITY_UNLIMITED then we should have 1 extra form open.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $new_titles[$delta] = 'Title for new child';
      $edit["single[$delta][inline_entity_form][title][0][value]"] = $new_titles[$delta];
    }
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText("IEF single simple {$host_node->label()} has been updated.");

    // Reset cache for nodes.
    $node_ids = [$host_node->id()];
    foreach ($host_node->single as $item) {
      $node_ids[] = $item->entity->id();
    }
    $this->nodeStorage->resetCache($node_ids);
    $host_node = $this->nodeStorage->load($host_node->id());
    // Check that titles were updated.
    foreach ($new_titles as $delta => $new_title) {
      $child_node = $host_node->single[$delta]->entity;
      $this->assertEqual($child_node->label(), $new_title, "Child $delta node title updated");
    }
  }

  /**
   * Ensures that an entity without bundles can be used with the simple widget.
   */
  public function testEntityWithoutBundle() {
    $this->drupalLogin($this->user);

    $edit = [
      'title[0][value]' => 'Node title',
      'field_ief_entity_no_bundle[0][inline_entity_form][name][0][value]' => 'Entity title',
    ];
    $this->drupalPostForm('node/add/ief_simple_entity_no_bundle', $edit, 'Save');

    $this->assertNodeByTitle('Node title', 'ief_simple_entity_no_bundle');
    $this->assertEntityByLabel('Entity title', 'entity_test__without_bundle');
  }

}
