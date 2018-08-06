<?php

namespace Drupal\entity_browser;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of entity browser widgets.
 */
class WidgetsCollection extends DefaultLazyPluginCollection {

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($a, $b) {
    $weight_a = $this->get($a)->getWeight();
    $weight_b = $this->get($b)->getWeight();

    return $weight_a < $weight_b ? -1 : 1;
  }

}
