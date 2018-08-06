<?php

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on namespaces.
 *
 * @Plugin(
 *   id = "namespace",
 *   weight = 0,
 *   name = @Translation("Namespace"),
 *   description = @Translation("Add config to packages that contain that package's machine name."),
 * )
 */
class FeaturesAssignmentNamespace extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $packages = $this->featuresManager->getPackages();
    $current_bundle = $this->assigner->getBundle();

    // Build an array of patterns.
    // Keys are short names while values are full machine names.
    // We need full names because existing packages may receive machine names
    // prefixed with a bundle name.
    $patterns = [];
    foreach ($packages as $package) {
      $machine_name = $package->getMachineName();
      $pattern = $current_bundle->getShortName($machine_name);
      $patterns[$pattern] = $machine_name;
    }
    $this->featuresManager->assignConfigByPattern($patterns);
  }

}
