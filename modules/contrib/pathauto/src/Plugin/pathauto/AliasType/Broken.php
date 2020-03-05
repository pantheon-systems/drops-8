<?php

namespace Drupal\pathauto\Plugin\pathauto\AliasType;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @AliasType(
 *   id = "broken",
 *   label = @Translation("Broken"),
 *   admin_label = @Translation("Broken/Missing"),
 *   category = @Translation("AliasType"),
 * )
 */
class Broken extends EntityAliasTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Broken type');
  }

}
