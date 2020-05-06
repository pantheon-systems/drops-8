<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;

/**
 * Defines a fallback plugin for missing webform handler plugins.
 *
 * @WebformHandler(
 *   id = "broken",
 *   label = @Translation("Broken/Missing"),
 *   category = @Translation("Broken"),
 *   description = @Translation("Broken/missing webform handler plugin."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class BrokenWebformHandler extends WebformHandlerBase {

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
   * @see \Drupal\webform\Plugin\WebformHandlerPluginCollection::initializePlugin
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

}
