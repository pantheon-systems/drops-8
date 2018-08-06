<?php

namespace Drupal\webform;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of webform handlers.
 */
class WebformHandlerPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($a_id, $b_id) {
    $a_weight = $this->get($a_id)->getWeight();
    $b_weight = $this->get($b_id)->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
