<?php

namespace Drupal\webform\Plugin\WebformVariant;

use Drupal\webform\Plugin\WebformVariantBase;

/**
 * Defines a fallback plugin for missing webform handler plugins.
 *
 * @WebformVariant(
 *   id = "broken",
 *   label = @Translation("Broken/Missing"),
 *   category = @Translation("Broken"),
 *   description = @Translation("Broken/missing webform handler plugin.")
 * )
 */
class BrokenWebformVariant extends WebformVariantBase {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $t_args = ['%plugin_id' => $this->getPluginId()];
    return [
      'message' => [
        '#markup' => $this->t('This %plugin_id handler is broken or missing. You might need to enable the original module and/or clear the cache.', $t_args),
      ],
    ];
  }

  /**
   * Set a broken handler's plugin id.
   *
   * This allows broken handlers to preserve the original handler's plugin ID.
   *
   * @param string $plugin_id
   *   The original handler's plugin ID.
   *
   * @see \Drupal\webform\Plugin\WebformVariantPluginCollection::initializePlugin
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

}
