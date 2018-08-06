<?php

namespace Drupal\entity_embed\Tests;

use Drupal\Core\Form\FormState;

/**
 * Tests the view mode entity embed display provided by entity_embed.
 *
 * @group entity_embed
 */
class ViewModeFieldFormatterTest extends EntityEmbedTestBase {

  private $plugins = [
    'view_mode:node.full',
    'view_mode:node.rss',
    'view_mode:node.search_index',
    'view_mode:node.search_result',
    'view_mode:node.teaser',
  ];

  /**
   * Tests view mode entity embed display.
   */
  public function testViewModeFieldFormatter() {
    // Ensure that view mode plugins have no configuration form.
    foreach ($this->plugins as $plugin) {
      $form = [];
      $form_state = new FormState();
      $display = $this->container->get('plugin.manager.entity_embed.display')
        ->createInstance($plugin, []);
      $display->setContextValue('entity', $this->node);
      $conf_form = $display->buildConfigurationForm($form, $form_state);
      $this->assertIdentical(array_keys($conf_form), []);
    }
  }

  /**
   * Tests filter using view mode entity embed display plugins.
   */
  public function testFilterViewModePlugins() {
    foreach ($this->plugins as $plugin) {
      $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="' . $plugin . '"></drupal-entity>';
      $settings = [];
      $settings['type'] = 'page';
      $settings['title'] = 'Test ' . $plugin . ' Entity Embed Display plugin';
      $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
      $node = $this->drupalCreateNode($settings);
      $this->drupalGet('node/' . $node->id());
      $plugin = explode('.', $plugin);
      $view_mode = str_replace('_', '-', end($plugin));
      $this->assertRaw('node--view-mode-' . $view_mode, 'Node rendered in the correct view mode: ' . $view_mode . '.');
    }
  }

}
