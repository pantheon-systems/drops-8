<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemPoweredByBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Powered by Drupal' block.
 *
 * @Block(
 *   id = "system_powered_by_block",
 *   admin_label = @Translation("Powered by Drupal")
 * )
 */
class SystemPoweredByBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array('#markup' => '<span>' . $this->t('Powered by <a href=":poweredby">Drupal</a>', array(':poweredby' => 'https://www.drupal.org')) . '</span>');
  }

}
