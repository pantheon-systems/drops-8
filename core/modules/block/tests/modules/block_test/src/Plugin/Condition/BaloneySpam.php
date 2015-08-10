<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Condition\BaloneySpam.
 */

namespace Drupal\block_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a 'baloney.spam' condition.
 *
 * @Condition(
 *   id = "baloney.spam",
 *   label = @Translation("Baloney spam"),
 * )
 *
 */
class BaloneySpam extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return 'Summary';
  }

}
