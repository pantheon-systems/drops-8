<?php

namespace Drupal\entity_embed\Tests;

/**
 * Tests Twig extension provided by entity_embed.
 *
 * @group entity_embed
 */
class EntityEmbedTwigTest extends EntityEmbedTestBase {

  /**
   *
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme'));
  }

  /**
   * Tests that the provided Twig extension loads the service appropriately.
   */
  public function testTwigExtensionLoaded() {
    $twig_service = \Drupal::service('twig');

    $ext = $twig_service->getExtension('entity_embed.twig.entity_embed_twig_extension');

    // @todo why is the string
    // 'Drupal\\entity_embed\\Twig\\EntityEmbedTwigExtension'
    // and not '\Drupal\entity_embed\Twig\EntityEmbedTwigExtension' ?
    $this->assertEqual(get_class($ext), 'Drupal\\entity_embed\\Twig\\EntityEmbedTwigExtension', 'Extension loaded successfully.');
  }

  /**
   * Tests that the Twig extension's filter produces expected output.
   */
  public function testEntityEmbedTwigFunction() {
    // Test embedding a node using entity ID.
    $this->drupalGet('entity_embed_twig_test/id');
    $this->assertText($this->node->body->value, 'Embedded node exists in page');

    // Test 'Label' Entity Embed Display plugin.
    $this->drupalGet('entity_embed_twig_test/label_plugin');
    $this->assertText($this->node->title->value, 'Title of the embedded node exists in page.');
    $this->assertNoText($this->node->body->value, 'Body of embedded node does not exists in page when "Label" plugin is used.');
    $this->assertLinkByHref('node/' . $this->node->id(), 0, 'Link to the embedded node exists when "Label" plugin is used.');

    // Test 'Label' Entity Embed Display plugin without linking to the node.
    $this->drupalGet('entity_embed_twig_test/label_plugin_no_link');
    $this->assertText($this->node->title->value, 'Title of the embedded node exists in page.');
    $this->assertNoText($this->node->body->value, 'Body of embedded node does not exists in page when "Label" plugin is used.');
    $this->assertNoLinkByHref('node/' . $this->node->id(), 0, 'Link to the embedded node does not exists.');
  }

}
