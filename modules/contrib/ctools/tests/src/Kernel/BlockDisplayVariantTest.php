<?php

namespace Drupal\Tests\ctools\Kernel;

use Drupal\ctools\Event\BlockVariantEvent;
use Drupal\ctools\Event\BlockVariantEvents;
use Drupal\ctools_block_display_test\Plugin\DisplayVariant\BlockDisplayVariant;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant
 * @group CTools
 */
class BlockDisplayVariantTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ctools', 'ctools_block_display_test', 'system', 'user'];

  /**
   * Tests that events are fired when manipulating a block variant.
   */
  public function testBlockDisplayVariantEvents() {
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    // Swap in a mock event dispatcher so we can spy on method calls.
    $this->container->set('event_dispatcher', $event_dispatcher->reveal());

    $variant = BlockDisplayVariant::create(
      $this->container,
      [],
      'foobar',
      []
    );
    // Set up the expected calls to the event dispatcher.
    $event = Argument::type(BlockVariantEvent::class);

    $event_dispatcher->dispatch(BlockVariantEvents::ADD_BLOCK, $event)
      ->shouldBeCalled();
    $event_dispatcher->dispatch(BlockVariantEvents::UPDATE_BLOCK, $event)
      ->shouldBeCalled();
    $event_dispatcher->dispatch(BlockVariantEvents::DELETE_BLOCK, $event)
      ->shouldBeCalled();

    $block_id = $variant->addBlock(['id' => 'system_powered_by_block']);
    $variant->updateBlock($block_id, []);
    $variant->removeBlock($block_id);
  }

}
