<?php

namespace Drupal\Tests\webform_options_custom\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform options custom entity test.
 *
 * @group webform_browser
 */
class WebformOptionsCustomEntityTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'webform',
    'webform_options_custom',
    'webform_options_custom_entity_test',
  ];

  /**
   * Test options custom entity.
   */
  public function testOptionsCustomEntity() {
    $webform = Webform::load('test_element_options_custom_ent');

    $node = Node::load(1);
    $this->drupalGet('/webform/test_element_options_custom_ent');

    // Check that data-option-value is populated with the node ids.
    $this->assertRaw('<div data-name="Room 1" data-option-value="1">Room 1</div>');
    $this->assertRaw('<div data-name="Room 2" data-option-value="2">Room 2</div>');
    $this->assertRaw('<div data-name="Room 3" data-option-value="3">Room 3</div>');

    // Check that data-descriptions are populated with the node titles.
    $this->assertRaw('data-descriptions="{&quot;1&quot;:&quot;This is room number #1. [1 remaining]&quot;,&quot;2&quot;:&quot;This is room number #2. [1 remaining]&quot;,&quot;3&quot;:&quot;This is room number #3. [1 remaining]&quot;}"');

    // Check that node link is used in the preview.
    // Please note that the description is used in the node's title.
    $this->postSubmission($webform, ['webform_options_custom_entity[select][]' => '1'], 'Preview');
    $this->assertRaw('<a href="' . $node->toUrl()->setAbsolute()->toString() . '" hreflang="en">Room 1 -- This is room number #1.</a>');
  }

}
