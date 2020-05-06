<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests the normalization of webform entity reference items.
 *
 * @group Webform
 */
class WebformEntityReferenceItemNormalizerTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rest', 'serialization', 'hal', 'webform', 'webform_node'];

  /**
   * Tests the normalization of a node with a webform entity reference.
   */
  public function testWebformEntityReferenceItemNormalization() {
    // Create node.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $webform_field = 'webform';

    // Set webform field to reference the contact webform and add data.
    $node->{$webform_field}->target_id = 'contact';
    $node->{$webform_field}->default_data = 'name: Please enter your name\r\nemail: Please enter a valid email address';
    $node->{$webform_field}->status = 1;
    $node->save();

    // Normalize the node.
    $serializer = $this->container->get('serializer');
    $normalized = $serializer->normalize($node, 'hal_json');
    $this->assertEqual($node->{$webform_field}->default_data, $normalized[$webform_field][0]['default_data']);
    $this->assertEqual($node->{$webform_field}->status, $normalized[$webform_field][0]['status']);

    // Denormalize the node.
    $new_node = $serializer->denormalize($normalized, Node::class, 'hal_json');
    $this->assertEqual($node->{$webform_field}->default_data, $new_node->{$webform_field}->default_data);
    $this->assertEqual($node->{$webform_field}->status, $new_node->{$webform_field}->status);
  }

}
