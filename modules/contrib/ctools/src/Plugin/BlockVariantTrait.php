<?php

namespace Drupal\ctools\Plugin;

use Drupal\ctools\Event\BlockVariantEvent;
use Drupal\ctools\Event\BlockVariantEvents;

/**
 * Provides methods for \Drupal\ctools\Plugin\BlockVariantInterface.
 */
trait BlockVariantTrait {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The plugin collection that holds the block plugins.
   *
   * @var \Drupal\ctools\Plugin\BlockPluginCollection
   */
  protected $blockPluginCollection;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::getRegionNames()
   */
  abstract public function getRegionNames();

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::getBlock()
   */
  public function getBlock($block_id) {
    return $this->getBlockCollection()->get($block_id);
  }

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::addBlock()
   */
  public function addBlock(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getBlockCollection()->addInstanceId($configuration['uuid'], $configuration);

    $block = $this->getBlock($configuration['uuid']);
    // Allow modules to react to the change.
    $event = new BlockVariantEvent($block, $this);
    $this->eventDispatcher()->dispatch(BlockVariantEvents::ADD_BLOCK, $event);

    return $configuration['uuid'];
  }

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::removeBlock()
   */
  public function removeBlock($block_id) {
    $block = $this->getBlock($block_id);
    $this->getBlockCollection()->removeInstanceId($block_id);

    // Allow modules to react to the change.
    $event = new BlockVariantEvent($block, $this);
    $this->eventDispatcher()->dispatch(BlockVariantEvents::DELETE_BLOCK, $event);

    return $this;
  }

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::updateBlock()
   */
  public function updateBlock($block_id, array $configuration) {
    $block = $this->getBlock($block_id);
    $existing_configuration = $block->getConfiguration();
    $this->getBlockCollection()->setInstanceConfiguration($block_id, $configuration + $existing_configuration);

    // Allow modules to react to the change.
    $event = new BlockVariantEvent($block, $this);
    $this->eventDispatcher()->dispatch(BlockVariantEvents::UPDATE_BLOCK, $event);

    return $this;
  }

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::getRegionAssignment()
   */
  public function getRegionAssignment($block_id) {
    $configuration = $this->getBlock($block_id)->getConfiguration();
    return isset($configuration['region']) ? $configuration['region'] : NULL;
  }

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::getRegionAssignments()
   */
  public function getRegionAssignments() {
    // Build an array of the region names in the right order.
    $empty = array_fill_keys(array_keys($this->getRegionNames()), []);
    $full = $this->getBlockCollection()->getAllByRegion();
    // Merge it with the actual values to maintain the ordering.
    return array_intersect_key(array_merge($empty, $full), $empty);
  }

  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::getRegionName()
   */
  public function getRegionName($region) {
    $regions = $this->getRegionNames();
    return isset($regions[$region]) ? $regions[$region] : '';
  }

  /**
   * Gets the block plugin manager.
   *
   * @return \Drupal\Core\Block\BlockManager
   *   The block plugin manager.
   */
  protected function getBlockManager() {
    if (!$this->blockManager) {
      $this->blockManager = \Drupal::service('plugin.manager.block');
    }
    return $this->blockManager;
  }

  /**
   * Returns the block plugins used for this display variant.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]|\Drupal\ctools\Plugin\BlockPluginCollection
   *   An array or collection of configured block plugins.
   */
  protected function getBlockCollection() {
    if (!$this->blockPluginCollection) {
      $this->blockPluginCollection = new BlockPluginCollection($this->getBlockManager(), $this->getBlockConfig());
    }
    return $this->blockPluginCollection;
  }

  /**
   * Gets the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected function eventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

  /**
   * Returns the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   */
  abstract protected function uuidGenerator();

  /**
   * Returns the configuration for stored blocks.
   *
   * @return array
   *   An array of block configuration, keyed by the unique block ID.
   */
  abstract protected function getBlockConfig();

}
