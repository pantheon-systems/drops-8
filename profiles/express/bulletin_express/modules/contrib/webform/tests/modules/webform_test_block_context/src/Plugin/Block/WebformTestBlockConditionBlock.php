<?php

namespace Drupal\webform_test_block_context\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'webform_test_block_context' block.
 *
 * @Block(
 *   id = "webform_test_block_context_block",
 *   admin_label = @Translation("Webform Block Condition"),
 *   category = @Translation("Webform Test")
 * )
 */
class WebformTestBlockConditionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '{This is a test of webform block contexts}',
    ];
  }

}
