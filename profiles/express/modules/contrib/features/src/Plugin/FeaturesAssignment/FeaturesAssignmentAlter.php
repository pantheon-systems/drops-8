<?php

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Class for excluding configuration from packages.
 *
 * @Plugin(
 *   id = "alter",
 *   weight = 0,
 *   name = @Translation("Alter"),
 *   description = @Translation("Alter configuration items before they are exported. Altering includes options such as removing permissions from roles."),
 *   config_route_name = "features.assignment_alter",
 *   default_settings = {
 *     "core" = TRUE,
 *     "uuid" = TRUE,
 *     "user_permissions" = TRUE,
 *   }
 * )
 */
class FeaturesAssignmentAlter extends FeaturesAssignmentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings($this->getPluginId());

    // Alter configuration items.
    if ($settings['core'] || $settings['uuid'] || $settings['user_permissions']) {
      $config_collection = $this->featuresManager->getConfigCollection();
      foreach ($config_collection as &$config) {
        $data = $config->getData();
        if ($settings['core']) {
          unset($data['_core']);
        }
        // Unset UUID for configuration entities.
        if ($settings['uuid'] && $config->getType() !== FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG) {
          unset($data['uuid']);
        }
        // Unset permissions for user roles. Doing so facilitates packaging
        // roles that may have permissions that relate to multiple packages.
        if ($settings['user_permissions'] && $config->getType() == 'user_role') {
          // Unset and not empty permissions data to prevent loss of configured
          // role permissions in the event of a feature revert.
          unset($data['permissions']);
        }
        $config->setData($data);
      }
      // Clean up the $config pass by reference.
      unset($config);

      // Register the updated data.
      $this->featuresManager->setConfigCollection($config_collection);
    }

  }

}
