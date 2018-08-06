<?php

namespace Drupal\Tests\webform_node\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the Webform node module cannot be uninstalled if webform nodes exist.
 *
 * @group webform_node
 */
class WebformNodeUninstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'filter', 'text', 'user', 'node', 'webform', 'webform_node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('webform');
    $this->installEntitySchema('webform_submission');
    $this->installSchema('webform', ['webform']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'node', 'webform', 'webform_node']);
    // For uninstall to work.
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests the webform_node_uninstall() method.
   */
  public function testWebformNodeUninstall() {
    module_load_include('install', 'webform_node');

    // Check that webform node module can not be installed.
    $this->assertNotEmpty(webform_node_requirements('install'), 'Webform node module can not be installed.');

    // No nodes exist.
    $validation_reasons = \Drupal::service('module_installer')->validateUninstall(['webform_node']);
    $this->assertEqual([], $validation_reasons, 'The webform_node module is not required.');

    $node = Node::create(['title' => $this->randomString(), 'type' => 'webform']);
    $node->save();

    // Check webform node module can't be ininstalled.
    $validation_reasons = \Drupal::service('module_installer')->validateUninstall(['webform_node']);
    $this->assertEqual(['To uninstall Webform node, delete all content that has the Webform content type.'], $validation_reasons['webform_node']);

    $node->delete();

    // Uninstall the Webform node module and check that all webform node have been deleted.
    \Drupal::service('module_installer')->uninstall(['webform_node']);
    $this->assertNull(NodeType::load('webform'), "The webform node type does not exist.");

    // Check that webform node module can be installed.
    $this->assertEmpty(webform_node_requirements('install'), 'Webform node module can be installed.');
  }

}
