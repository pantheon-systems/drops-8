<?php

namespace Drupal\entity_embed\Tests;

use Drupal\Core\Form\FormState;

/**
 * Tests the entity reference field formatters provided by entity_embed.
 *
 * @group entity_embed
 */
class EntityReferenceFieldFormatterTest extends EntityEmbedTestBase {

  /**
   * The test 'menu' entity.
   *
   * @var \Drupal\Core\Menu\MenuInterface
   */
  protected $menu;

  /**
   *
   */
  protected function setUp() {
    parent::setUp();

    // Add a new menu entity which does not has a view controller.
    $this->menu = entity_create('menu', array(
      'id' => 'menu_name',
      'label' => 'Label',
      'description' => 'Description text',
    ));
    $this->menu->save();
  }

  /**
   * Tests entity reference field formatters.
   */
  public function testEntityReferenceFieldFormatter() {
    // Ensure that entity reference field formatters are available as plugins.
    $this->assertAvailableDisplayPlugins($this->node, [
      'entity_reference:entity_reference_label',
      'entity_reference:entity_reference_entity_id',
      'view_mode:node.full',
      'view_mode:node.rss',
      'view_mode:node.search_index',
      'view_mode:node.search_result',
      'view_mode:node.teaser',
    ]);

    $this->container->get('config.factory')->getEditable('entity_embed.settings')
      ->set('rendered_entity_mode', TRUE)->save();
    $this->container->get('plugin.manager.entity_embed.display')->clearCachedDefinitions();

    $this->assertAvailableDisplayPlugins($this->node, [
      'entity_reference:entity_reference_label',
      'entity_reference:entity_reference_entity_id',
      'entity_reference:entity_reference_entity_view',
    ]);

    // Ensure that correct form attributes are returned for
    // 'entity_reference:entity_reference_entity_id' plugin.
    $form = array();
    $form_state = new FormState();
    $display = $this->container->get('plugin.manager.entity_embed.display')->createInstance('entity_reference:entity_reference_entity_id', array());
    $display->setContextValue('entity', $this->node);
    $conf_form = $display->buildConfigurationForm($form, $form_state);
    $this->assertIdentical(array_keys($conf_form), array());

    // Ensure that correct form attributes are returned for
    // 'entity_reference:entity_reference_entity_view' plugin.
    $form = array();
    $form_state = new FormState();
    $display = $this->container->get('plugin.manager.entity_embed.display')->createInstance('entity_reference:entity_reference_entity_view', array());
    $display->setContextValue('entity', $this->node);
    $conf_form = $display->buildConfigurationForm($form, $form_state);
    $this->assertIdentical($conf_form['view_mode']['#type'], 'select');
    $this->assertIdentical((string) $conf_form['view_mode']['#title'], 'View mode');

    // Ensure that correct form attributes are returned for
    // 'entity_reference:entity_reference_label' plugin.
    $form = array();
    $form_state = new FormState();
    $display = $this->container->get('plugin.manager.entity_embed.display')->createInstance('entity_reference:entity_reference_label', array());
    $display->setContextValue('entity', $this->node);
    $conf_form = $display->buildConfigurationForm($form, $form_state);
    $this->assertIdentical(array_keys($conf_form), array('link'));
    $this->assertIdentical($conf_form['link']['#type'], 'checkbox');
    $this->assertIdentical((string) $conf_form['link']['#title'], 'Link label to the referenced entity');

    // Ensure that 'Rendered Entity' plugin is not available for an entity not
    // having a view controller.
    $plugin_options = $this->container->get('plugin.manager.entity_embed.display')->getDefinitionOptionsForEntity($this->menu);
    $this->assertFalse(array_key_exists('entity_reference:entity_reference_entity_view', $plugin_options), "The 'Rendered entity' plugin is not available.");
  }

  /**
   * Tests filter using entity reference Entity Embed Display plugins.
   */
  public function testFilterEntityReferencePlugins() {
    // Test 'Label' Entity Embed Display plugin.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="entity_reference:entity_reference_label" data-entity-embed-display-settings=\'{"link":1}\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity_reference:entity_reference_label Entity Embed Display plugin';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->title->value, 'Title of the embedded node exists in page.');
    $this->assertNoText($this->node->body->value, 'Body of embedded node does not exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
    $this->assertLinkByHref('node/' . $this->node->id(), 0, 'Link to the embedded node exists.');

    // Test 'Entity ID' Entity Embed Display plugin.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="entity_reference:entity_reference_entity_id">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity_reference:entity_reference_entity_id Entity Embed Display plugin';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->id(), 'ID of the embedded node exists in page.');
    $this->assertNoText($this->node->title->value, 'Title of the embedded node does not exists in page.');
    $this->assertNoText($this->node->body->value, 'Body of embedded node does not exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
    $this->assertNoLinkByHref('node/' . $this->node->id(), 'Link to the embedded node does not exists.');

    // Test 'Rendered entity' Entity Embed Display plugin.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="entity_reference:entity_reference_entity_view" data-entity-embed-display-settings=\'{"view_mode":"teaser"}\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity_reference:entity_reference_label Entity Embed Display plugin';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->body->value, 'Body of embedded node does not exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
  }

}
