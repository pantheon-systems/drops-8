<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform options entity reference limit test.
 *
 * @group webform_browser
 */
class WebformOptionsLimitEntityReferenceTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'webform',
    'node',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit.
   */
  public function testOptionsLimit() {
    $webform = Webform::load('test_handler_options_limit_ent');

    // Must login because webform and entity references are cached for
    // anonymous users.
    $this->drupalLogin($this->rootUser);

    // Check the entity select is not available.
    $this->drupalGet('/webform/test_handler_options_limit_ent');
    $this->assertRaw('options_limits_entity_select is not available');

    // Create three page nodes.
    $this->createContentType(['type' => 'page']);
    $node_1 = $this->createNode();
    $node_2 = $this->createNode();
    $node_3 = $this->createNode();

    // Check the entity select options are now populated.
    $this->drupalGet('/webform/test_handler_options_limit_ent');
    $this->assertNoRaw('options_limits_entity_select is not available');
    $this->assertRaw('<option value="' . $node_1->id() . '">');
    $this->assertRaw('<option value="' . $node_2->id() . '">');
    $this->assertRaw('<option value="' . $node_3->id() . '">');

    // Select node 1 three times.
    $this->postSubmission($webform, ['options_limits_entity_select' => [$node_1->id()]]);
    $this->postSubmission($webform, ['options_limits_entity_select' => [$node_1->id()]]);
    $this->postSubmission($webform, ['options_limits_entity_select' => [$node_1->id()]]);

    // Check the node is now disabled.
    $this->drupalGet('/webform/test_handler_options_limit_ent');
    $this->assertRaw('data-webform-select-options-disabled="1"');
  }

}
