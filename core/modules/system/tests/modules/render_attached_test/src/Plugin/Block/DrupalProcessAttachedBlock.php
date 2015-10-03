<?php

/**
 * @file
 * Contains \Drupal\render_attached_test\Plugin\Block\DrupalProcessAttachedBlock.
 */

namespace Drupal\render_attached_test\Plugin\Block;

use Drupal\render_attached_test\Controller\TestController;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * A block we can use to test caching of #attached headers.
 *
 * @Block(
 *   id = "drupal_process_attached_block",
 *   admin_label = @Translation("DrupalProcessAttachedBlock")
 * )
 *
 * @see \Drupal\system\Tests\Render\HtmlResponseAttachmentsTest
 */
class DrupalProcessAttachedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Grab test attachment fixtures from
    // Drupal\render_attached_test\Controller\TestController.
    $controller = new TestController();
    $attached = BubbleableMetadata::mergeAttachments($controller->feed(), $controller->head());
    $attached = BubbleableMetadata::mergeAttachments($attached, $controller->header());
    $attached = BubbleableMetadata::mergeAttachments($attached, $controller->teapotHeaderStatus());

    // Use drupal_process_attached() to attach all the #attached stuff.
    drupal_process_attached($attached);

    // Return some arbitrary markup so the block doesn't disappear.
    return ['#markup' => 'Headers handled by drupal_process_attached().'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
