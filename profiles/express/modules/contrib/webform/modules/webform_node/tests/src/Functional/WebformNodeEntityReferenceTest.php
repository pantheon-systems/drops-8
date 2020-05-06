<?php

namespace Drupal\Tests\webform_node\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests for webform node entity references.
 *
 * @group WebformNode
 */
class WebformNodeEntityReferenceTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'webform', 'webform_node', 'webform_node_test_multiple'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['webform_node_test_multiple_a', 'webform_node_test_multiple_b'];

  /**
   * Tests webform node entity references.
   */
  public function testEntityReference() {
    $user_data = \Drupal::service('user.data');

    $this->drupalLogin($this->rootUser);

    // Check that both webform A & B are being displayed.
    $this->drupalGet('/node/1');
    $this->assertRaw('webform_test_multiple_a');
    $this->assertRaw('textfield_a');
    $this->assertRaw('webform_test_multiple_b');
    $this->assertRaw('textfield_a');

    /**************************************************************************/

    // Check test form B (B is the default because its weight is -1).
    $this->drupalGet('/node/1/webform/test');
    $this->assertNoRaw('textfield_a');
    $this->assertRaw('textfield_b');

    // Check result webform B.
    $this->drupalGet('/node/1/webform/results/submissions');
    $this->assertNoRaw('textfield_a');
    $this->assertRaw('textfield_b');

    // Check export webform B.
    $this->drupalGet('/node/1/webform/results/download');
    $this->assertNoRaw('textfield_a');
    $this->assertRaw('textfield_b');

    // Check user data is NULL.
    $this->assertNull($user_data->get('webform_node', $this->rootUser->id(), 1));

    /**************************************************************************/

    // Select webform A.
    $this->drupalGet('/node/1/webform/test');
    $this->clickLink('Test: Webform Node Multiple A');

    // Check user data is set to webform A.
    $this->assertEqual(['target_id' => 'webform_node_test_multiple_a'], $user_data->get('webform_node', $this->rootUser->id(), 1));

    // Check test webform A.
    $this->drupalGet('/node/1/webform/test');
    $this->assertRaw('textfield_a');
    $this->assertNoRaw('textfield_b');

    // Check result webform A.
    $this->drupalGet('/node/1/webform/results/submissions');
    $this->assertRaw('textfield_a');
    $this->assertNoRaw('textfield_b');

    // Check export webform A.
    $this->drupalGet('/node/1/webform/results/download');
    $this->assertRaw('textfield_a');
    $this->assertNoRaw('textfield_b');

    /**************************************************************************/

    // Select webform A.
    $this->drupalGet('/node/1/webform/test');
    $this->clickLink('Test: Webform Node Multiple A');

    // Check user data is set to webform A.
    $this->assertEqual(['target_id' => 'webform_node_test_multiple_a'], $user_data->get('webform_node', $this->rootUser->id(), 1));

    // Check test webform A.
    $this->drupalGet('/node/1/webform/test');
    $this->assertRaw('textfield_a');
    $this->assertNoRaw('textfield_b');

    // Check result webform A.
    $this->drupalGet('/node/1/webform/results/submissions');
    $this->assertRaw('textfield_a');
    $this->assertNoRaw('textfield_b');

    // Check export webform A.
    $this->drupalGet('/node/1/webform/results/download');
    $this->assertRaw('textfield_a');
    $this->assertNoRaw('textfield_b');

    /**************************************************************************/

    // Delete the node.
    Node::load(1)->delete();

    // Check user data is NULL (aka deleted).
    $this->assertNull($user_data->get('webform_node', $this->rootUser->id(), 1));
  }

}
