<?php

/**
 * @file
 * Contains \Drupal\linkit\MatcherCollection.
 */

namespace Drupal\linkit;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of matchers.
 */
class MatcherCollection extends DefaultLazyPluginCollection {

  /**
   * All possible matcher IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\linkit\MatcherInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a_weight = $this->get($aID)->getWeight();
    $b_weight = $this->get($bID)->getWeight();
    if ($a_weight == $b_weight) {
      return strnatcasecmp($this->get($aID)->getLabel(), $this->get($bID)->getLabel());
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
