<?php

namespace Drupal\entity_embed\Tests;

/**
 * Tests the hooks provided by entity_embed module.
 *
 * @group entity_embed
 */
class EntityEmbedHooksTest extends EntityEmbedTestBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   *
   */
  protected function setUp() {
    parent::setUp();
    $this->state = $this->container->get('state');
  }

  /**
   * Tests hook_entity_embed_display_plugins_alter().
   */
  public function testDisplayPluginAlterHooks() {
    // Enable entity_embed_test.module's
    // hook_entity_embed_display_plugins_alter() implementation and ensure it is
    // working as designed.
    $this->state->set('entity_embed_test_entity_embed_display_plugins_alter', TRUE);
    $plugins = $this->container->get('plugin.manager.entity_embed.display')
      ->getDefinitionOptionsForEntity($this->node);
    // Ensure that name of each plugin is prefixed with 'testing_hook:'.
    foreach ($plugins as $plugin => $plugin_info) {
      $this->assertTrue(strpos($plugin, 'testing_hook:') === 0, 'Name of the plugin is prefixed by hook_entity_embed_display_plugins_alter()');
    }
  }

  /**
   * Tests the hooks provided by entity_embed module.
   *
   * This method tests all the hooks provided by entity_embed except
   * hook_entity_embed_display_plugins_alter, which is tested by a separate
   * method.
   */
  public function testEntityEmbedHooks() {
    // Enable entity_embed_test.module's hook_entity_embed_alter()
    // implementation and ensure it is working as designed.
    $this->state->set('entity_embed_test_entity_embed_alter', TRUE);
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="default" data-entity-embed-display-settings=\'{"view_mode":"teaser"}\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test hook_entity_embed_alter()';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->body->value, 'Embedded node exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
    // Ensure that embedded node's title has been replaced.
    $this->assertText('Title set by hook_entity_embed_alter', 'Title of the embedded node is replaced by hook_entity_embed_alter()');
    $this->assertNoText($this->node->title->value, 'Original title of the embedded node is not visible.');
    $this->state->set('entity_embed_test_entity_embed_alter', FALSE);

    // Enable entity_embed_test.module's hook_entity_embed_context_alter()
    // implementation and ensure it is working as designed.
    $this->state->set('entity_embed_test_entity_embed_context_alter', TRUE);
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="default" data-entity-embed-display-settings=\'{"view_mode":"teaser"}\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test hook_entity_embed_context_alter()';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
    // To ensure that 'label' plugin is used, verify that the body of the
    // embedded node is not visible and the title links to the embedded node.
    $this->assertNoText($this->node->body->value, 'Body of the embedded node does not exists in page.');
    $this->assertText('Title set by hook_entity_embed_context_alter', 'Title of the embedded node is replaced by hook_entity_embed_context_alter()');
    $this->assertNoText($this->node->title->value, 'Original title of the embedded node is not visible.');
    $this->assertLinkByHref('node/' . $this->node->id(), 0, 'Link to the embedded node exists.');
    $this->state->set('entity_embed_test_entity_embed_context_alter', FALSE);
  }

}
