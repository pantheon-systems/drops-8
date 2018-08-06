<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Tests the Entity Reference Widget.
 *
 * @group entity_browser
 */
class EntityReferenceWidgetTest extends EntityBrowserJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['access test_entity_browser_iframe_node_view entity browser pages']);
    $this->grantPermissions($role, ['bypass node access']);

  }

  /**
   * Tests Entity Reference widget.
   */
  public function testEntityReferenceWidget() {

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create an entity_reference field to test the widget.
    FieldStorageConfig::create([
      'field_name' => 'field_entity_reference1',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_entity_reference1',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Referenced articles',
      'settings' => [],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default');

    $form_display->setComponent('field_entity_reference1', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'test_entity_browser_iframe_node_view',
        'open' => TRUE,
        'field_widget_edit' => TRUE,
        'field_widget_remove' => TRUE,
        'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        'field_widget_display' => 'label',
        'field_widget_display_settings' => [],
      ],
    ])->save();

    // Create a dummy node that will be used as target.
    $target_node = Node::create([
      'title' => 'Target example node 1',
      'type' => 'article',
    ]);
    $target_node->save();

    $this->drupalGet('/node/add/article');
    $page->fillField('title[0][value]', 'Referencing node 1');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_entity_browser_iframe_node_view');
    $this->waitForAjaxToFinish();
    $page->checkField('edit-entity-browser-select-node1');
    $page->pressButton('Select entities');
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    $page->pressButton('Save');

    $assert_session->pageTextContains('Article Referencing node 1 has been created.');
    $nid = $this->container->get('entity.query')->get('node')->condition('title', 'Referencing node 1')->execute();
    $nid = reset($nid);

    $this->drupalGet('node/' . $nid . '/edit');
    $assert_session->pageTextContains('Target example node 1');
    // Make sure both "Edit" and "Remove" buttons are visible.
    $assert_session->buttonExists('edit-field-entity-reference1-current-items-0-remove-button');
    $assert_session->buttonExists('edit-field-entity-reference1-current-items-0-edit-button');

    // Test whether changing these definitions on the browser config effectively
    // change the visibility of the buttons.
    $form_display->setComponent('field_entity_reference1', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'test_entity_browser_iframe_node_view',
        'open' => TRUE,
        'field_widget_edit' => FALSE,
        'field_widget_remove' => FALSE,
        'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        'field_widget_display' => 'label',
        'field_widget_display_settings' => [],
      ],
    ])->save();
    $this->drupalGet('node/' . $nid . '/edit');
    $assert_session->buttonNotExists('edit-field-entity-reference1-current-items-0-remove-button');
    $assert_session->buttonNotExists('edit-field-entity-reference1-current-items-0-edit-button');

    // Set them to visible again.
    $form_display->setComponent('field_entity_reference1', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'test_entity_browser_iframe_node_view',
        'open' => TRUE,
        'field_widget_edit' => TRUE,
        'field_widget_remove' => TRUE,
        'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        'field_widget_display' => 'label',
        'field_widget_display_settings' => [],
      ],
    ])->save();
    $this->drupalGet('node/' . $nid . '/edit');
    $assert_session->buttonExists('edit-field-entity-reference1-current-items-0-remove-button');
    $assert_session->buttonExists('edit-field-entity-reference1-current-items-0-edit-button');

    // Test the "Remove" button on the widget works.
    $page->pressButton('Remove');
    $this->waitForAjaxToFinish();
    $assert_session->pageTextNotContains('Target example node 1');

    // Verify that if the user cannot edit the entity, the "Edit" button does
    // not show up, even if configured to.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $role->revokePermission('bypass node access')->trustData()->save();
    $this->drupalGet('node/add/article');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_entity_browser_iframe_node_view');
    $this->waitForAjaxToFinish();
    $page->checkField('edit-entity-browser-select-node1');
    $page->pressButton('Select entities');
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    $assert_session->buttonNotExists('edit-field-entity-reference1-current-items-0-edit-button');

  }

}
