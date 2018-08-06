<?php

namespace Drupal\ctools\Event;

/**
 * Contains all events dispatched while manipulating blocks in a variant.
 */
final class BlockVariantEvents {

  /**
   * The name of the event triggered when a block is added to a variant.
   *
   * This event allows modules to react to a block being added to a variant. The
   * event listener method receives a \Drupal\ctools\Event\BlockVariantEvent
   * instance.
   *
   * @Event
   *
   * @var string
   */
  const ADD_BLOCK = 'block.add';

  /**
   * The name of the event triggered when a block is modified in a variant.
   *
   * This event allows modules to react to a block being modified in a variant.
   * The event listener method receives a \Drupal\ctools\Event\BlockVariantEvent
   * instance.
   *
   * @Event
   *
   * @var string
   */
  const UPDATE_BLOCK = 'block.update';

  /**
   * The name of the event triggered when a block is removed from a variant.
   *
   * This event allows modules to react to a block being removed from a variant.
   * The event listener method receives a \Drupal\ctools\Event\BlockVariantEvent
   * instance.
   *
   * @Event
   *
   * @var string
   */
  const DELETE_BLOCK = 'block.delete';

}
