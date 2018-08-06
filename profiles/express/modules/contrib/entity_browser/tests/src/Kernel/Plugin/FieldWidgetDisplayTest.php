<?php

namespace Drupal\Tests\entity_browser\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests field widget display plugins.
 *
 * @group entity_browser
 */
class FieldWidgetDisplayTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser', 'image', 'comment',
  ];

  /**
   * Field widget display plugin manager.
   *
   * @var \Drupal\entity_browser\FieldWidgetDisplayManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pluginManager = $this->container->get('plugin.manager.entity_browser.field_widget_display');

    $image_style = $this->container->get('entity_type.manager')->getStorage('image_style');
    $image_style->create(['name' => 'thumbnail', 'label' => 'Thumbnail'])->save();
    $image_style->create(['name' => 'large', 'label' => 'Large'])->save();

    $comment_type = $this->container->get('entity_type.manager')->getStorage('comment_type');
    $comment_type->create(['id' => 'comment', 'label' => 'Comment'])->save();

    $view_mode = $this->container->get('entity_type.manager')->getStorage('entity_view_mode');
    $view_mode->create(['id' => 'comment.full', 'targetEntityType' => 'comment'])->save();
  }

  /**
   * Test field widget display plugins configuration and dependencies.
   */
  public function testDefaultConfiguration() {
    // Check default configuration for image thumbnail plugin.
    $image_thumbnail_plugin = $this->pluginManager->createInstance('thumbnail');
    $this->assertEquals(['image_style' => 'thumbnail'], $image_thumbnail_plugin->defaultConfiguration());
    $this->assertEquals(['image_style' => 'thumbnail'], $image_thumbnail_plugin->getConfiguration());
    $this->assertEquals(['config' => [0 => 'image.style.thumbnail']], $image_thumbnail_plugin->calculateDependencies());
    // Set configuration different then default.
    $image_thumbnail_plugin->setConfiguration(['image_style' => 'large']);
    $this->assertEquals(['image_style' => 'thumbnail'], $image_thumbnail_plugin->defaultConfiguration());
    $this->assertEquals(['image_style' => 'large'], $image_thumbnail_plugin->getConfiguration());
    $this->assertEquals(['config' => [0 => 'image.style.large']], $image_thumbnail_plugin->calculateDependencies());

    // Check default configuration for rendered entity plugin.
    $rendered_entity_plugin = $this->pluginManager->createInstance('rendered_entity', ['entity_type' => 'comment']);
    $this->assertEquals(['view_mode' => 'default'], $rendered_entity_plugin->defaultConfiguration());
    $this->assertEquals(['view_mode' => 'default', 'entity_type' => 'comment'], $rendered_entity_plugin->getConfiguration());
    $this->assertEquals([], $rendered_entity_plugin->calculateDependencies());
    // Set configuration different then default.
    $rendered_entity_plugin->setConfiguration(['entity_type' => 'comment', 'view_mode' => 'full']);
    $this->assertEquals(['view_mode' => 'default'], $rendered_entity_plugin->defaultConfiguration());
    $this->assertEquals(['view_mode' => 'full', 'entity_type' => 'comment'], $rendered_entity_plugin->getConfiguration());
    $this->assertEquals(['config' => [0 => 'core.entity_view_mode.comment.full']], $rendered_entity_plugin->calculateDependencies());
  }

}
