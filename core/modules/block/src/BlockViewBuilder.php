<?php

/**
 * @file
 * Contains \Drupal\block\BlockViewBuilder.
 */

namespace Drupal\block;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a Block view builder.
 */
class BlockViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\block\BlockInterface[] $entities */
    $build = array();
    foreach ($entities as  $entity) {
      $entity_id = $entity->id();
      $plugin = $entity->getPlugin();
      $plugin_id = $plugin->getPluginId();
      $base_id = $plugin->getBaseId();
      $derivative_id = $plugin->getDerivativeId();
      $configuration = $plugin->getConfiguration();

      // Create the render array for the block as a whole.
      // @see template_preprocess_block().
      $build[$entity_id] = array(
        '#theme' => 'block',
        '#attributes' => array(),
        // All blocks get a "Configure block" contextual link.
        '#contextual_links' => array(
          'block' => array(
            'route_parameters' => array('block' => $entity->id()),
          ),
        ),
        '#weight' => $entity->getWeight(),
        '#configuration' => $configuration,
        '#plugin_id' => $plugin_id,
        '#base_plugin_id' => $base_id,
        '#derivative_plugin_id' => $derivative_id,
        '#id' => $entity->id(),
        '#cache' => [
          'keys' => ['entity_view', 'block', $entity->id()],
          'contexts' => $plugin->getCacheContexts(),
          'tags' => Cache::mergeTags(
            $this->getCacheTags(), // Block view builder cache tag.
            $entity->getCacheTags(), // Block entity cache tag.
            $plugin->getCacheTags() // Block plugin cache tags.
          ),
          'max-age' => $plugin->getCacheMaxAge(),
        ],
        '#pre_render' => [
          [$this, 'buildBlock'],
        ],
        // Add the entity so that it can be used in the #pre_render method.
        '#block' => $entity,
      );
      $build[$entity_id]['#configuration']['label'] = SafeMarkup::checkPlain($configuration['label']);

      // Don't run in ::buildBlock() to ensure cache keys can be altered. If an
      // alter hook wants to modify the block contents, it can append another
      // #pre_render hook.
      $this->moduleHandler()->alter(array('block_view', "block_view_$base_id"), $build[$entity_id], $plugin);
    }
    return $build;
  }

  /**
   * #pre_render callback for building a block.
   *
   * Renders the content using the provided block plugin, and then:
   * - if there is no content, aborts rendering, and makes sure the block won't
   *   be rendered.
   * - if there is content, moves the contextual links from the block content to
   *   the block itself.
   */
  public function buildBlock($build) {
    $content = $build['#block']->getPlugin()->build();
    // Remove the block entity from the render array, to ensure that blocks
    // can be rendered without the block config entity.
    unset($build['#block']);
    if ($content !== NULL && !Element::isEmpty($content)) {
      // Place the $content returned by the block plugin into a 'content' child
      // element, as a way to allow the plugin to have complete control of its
      // properties and rendering (e.g., its own #theme) without conflicting
      // with the properties used above, or alternate ones used by alternate
      // block rendering approaches in contrib (e.g., Panels). However, the use
      // of a child element is an implementation detail of this particular block
      // rendering approach. Semantically, the content returned by the plugin
      // "is the" block, and in particular, #attributes and #contextual_links is
      // information about the *entire* block. Therefore, we must move these
      // properties from $content and merge them into the top-level element.
      foreach (array('#attributes', '#contextual_links') as $property) {
        if (isset($content[$property])) {
          $build[$property] += $content[$property];
          unset($content[$property]);
        }
      }
      $build['content'] = $content;
    }
    // Either the block's content is completely empty, or it consists only of
    // cacheability metadata.
    else {
      // Abort rendering: render as the empty string and ensure this block is
      // render cached, so we can avoid the work of having to repeatedly
      // determine whether the block is empty. E.g. modifying or adding entities
      // could cause the block to no longer be empty.
      $build = array(
        '#markup' => '',
        '#cache' => $build['#cache'],
      );
      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    return $build;
   }

}
