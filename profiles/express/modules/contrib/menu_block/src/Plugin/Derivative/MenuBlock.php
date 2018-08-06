<?php

namespace Drupal\menu_block\Plugin\Derivative;

use Drupal\system\Plugin\Derivative\SystemMenuBlock;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\system\Plugin\Block\SystemMenuBlock
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
      $this->derivatives[$menu] = $base_plugin_definition;
      $this->derivatives[$menu]['admin_label'] = $entity->label();
      $this->derivatives[$menu]['config_dependencies']['config'] = array($entity->getConfigDependencyName());
    }
    return $this->derivatives;
  }

}
