<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Entity\EntityReferenceSelection\EntityReferenceSelectionSortTest.
 */

namespace Drupal\KernelTests\Core\Entity\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests sorting referenced items.
 *
 * @group entity_reference
 */
class EntityReferenceSelectionSortTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  protected function setUp() {
    parent::setUp();

    // Create an Article node type.
    $article = NodeType::create(array(
      'type' => 'article',
    ));
    $article->save();

    // Test as a non-admin.
    $normal_user = $this->createUser(array(), array('access content'));
    \Drupal::currentUser()->setAccount($normal_user);
  }

  /**
   * Assert sorting by field and property.
   */
  public function testSort() {
    // Add text field to entity, to sort by.
    FieldStorageConfig::create(array(
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'type' => 'text',
      'entity_types' => array('node'),
    ))->save();

    FieldConfig::create([
      'label' => 'Text Field',
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => array(),
      'required' => FALSE,
    ])->save();

    // Build a set of test data.
    $node_values = array(
      'published1' => array(
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published1 (<&>)',
        'uid' => 1,
        'field_text' => array(
          array(
            'value' => 1,
          ),
        ),
      ),
      'published2' => array(
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published2 (<&>)',
        'uid' => 1,
        'field_text' => array(
          array(
            'value' => 2,
          ),
        ),
      ),
    );

    $nodes = array();
    $node_labels = array();
    foreach ($node_values as $key => $values) {
      $node = Node::create($values);
      $node->save();
      $nodes[$key] = $node;
      $node_labels[$key] = Html::escape($node->label());
    }

    $selection_options = array(
      'target_type' => 'node',
      'handler' => 'default',
      'handler_settings' => array(
        'target_bundles' => NULL,
        // Add sorting.
        'sort' => array(
          'field' => 'field_text.value',
          'direction' => 'DESC',
        ),
      ),
    );
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance($selection_options);

    // Not only assert the result, but make sure the keys are sorted as
    // expected.
    $result = $handler->getReferenceableEntities();
    $expected_result = array(
      $nodes['published2']->id() => $node_labels['published2'],
      $nodes['published1']->id() => $node_labels['published1'],
    );
    $this->assertIdentical($result['article'], $expected_result, 'Query sorted by field returned expected values.');

    // Assert sort by base field.
    $selection_options['handler_settings']['sort'] = array(
      'field' => 'nid',
      'direction' => 'ASC',
    );
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance($selection_options);
    $result = $handler->getReferenceableEntities();
    $expected_result = array(
      $nodes['published1']->id() => $node_labels['published1'],
      $nodes['published2']->id() => $node_labels['published2'],
    );
    $this->assertIdentical($result['article'], $expected_result, 'Query sorted by property returned expected values.');
  }

}
