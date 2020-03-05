<?php

namespace Drupal\ctools_block_display_test\Plugin\DisplayVariant;

use Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant as BaseBlockDisplayVariant;

class BlockDisplayVariant extends BaseBlockDisplayVariant {

  /**
   * {@inheritdoc}
   */
  public function getRegionNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
