<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\webform\Plugin\WebformVariant\BrokenWebformVariant;

/**
 * A collection of webform variants.
 */
class WebformVariantPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function sortHelper($a_id, $b_id) {
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $a */
    $a = $this->get($a_id);
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $b */
    $b = $this->get($b_id);

    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();
    if ($a_weight == $b_weight) {
      return strnatcasecmp($a->getVariantId(), $b->getVariantId());
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);

    // If the initialized variant is broken preserve the original
    // variant's plugin ID.
    // @see \Drupal\webform\Plugin\WebformVariant\BrokenWebformVariant::setPluginId
    $plugin = $this->get($instance_id);
    if ($plugin instanceof BrokenWebformVariant) {
      $original_id = $this->configurations[$instance_id]['id'];
      $plugin->setPluginId($original_id);
    }
  }

}
