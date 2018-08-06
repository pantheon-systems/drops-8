<?php

namespace Drupal\entity_browser\Tests;

use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity browser form element.
 *
 * @group entity_browser
 */
class FormElementTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_browser_test', 'node', 'views'];

  /**
   * Test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container
      ->get('entity_type.manager')
      ->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'page',
      ])->save();

    $this->nodes[] = $this->drupalCreateNode();
    $this->nodes[] = $this->drupalCreateNode();
  }

  /**
   * Tests the Entity browser form element.
   */
  public function testFormElement() {
    $this->drupalGet('/test-element');
    $this->assertLink('Select entities', 0, 'Trigger link found.');
    $this->assertFieldByXPath("//input[@type='hidden' and @id='edit-fancy-entity-browser-target']", '', "Entity browser's hidden element found.");

    $edit = [
      'fancy_entity_browser[entity_ids]' => $this->nodes[0]->getEntityTypeId() . ':' . $this->nodes[0]->id() . ' ' . $this->nodes[1]->getEntityTypeId() . ':' . $this->nodes[1]->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $expected = 'Selected entities: ' . $this->nodes[0]->label() . ', ' . $this->nodes[1]->label();
    $this->assertText($expected, 'Selected entities detected.');

    $default_entity = $this->nodes[0]->getEntityTypeId() . ':' . $this->nodes[0]->id();
    $this->drupalGet('/test-element', ['query' => ['default_entity' => $default_entity, 'selection_mode' => EntityBrowserElement::SELECTION_MODE_EDIT]]);
    $this->assertLink('Select entities', 0, 'Trigger link found.');
    $this->assertFieldByXPath("//input[@type='hidden' and @id='edit-fancy-entity-browser-target']", $default_entity, "Entity browser's hidden element found.");

    $edit = [
      'fancy_entity_browser[entity_ids]' => $this->nodes[1]->getEntityTypeId() . ':' . $this->nodes[1]->id() . ' ' . $this->nodes[0]->getEntityTypeId() . ':' . $this->nodes[0]->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $expected = 'Selected entities: ' . $this->nodes[1]->label() . ', ' . $this->nodes[0]->label();
    $this->assertText($expected, 'Selected entities detected.');
  }

}
