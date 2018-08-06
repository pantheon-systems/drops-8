<?php

namespace Drupal\features;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Interface for package assignment classes.
 */
interface FeaturesAssignmentMethodInterface extends PluginInspectionInterface {

  /**
   * Injects the features manager.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager to be used to retrieve the configuration list and
   *   the already assigned packages.
   */
  public function setFeaturesManager(FeaturesManagerInterface $features_manager);

  /**
   * Injects the features assigner.
   *
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The features assigner to be used to retrieve the bundle configuration.
   */
  public function setAssigner(FeaturesAssignerInterface $assigner);

  /**
   * Injects the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager to be used to retrieve entity information.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager);

  /**
   * Injects the configuration factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory to be used to retrieve configuration values.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory);

  /**
   * Performs package assignment.
   *
   * @param bool $force
   *   (optional) If TRUE, assign config regardless of restrictions such as it
   *   being already assigned to a package.
   */
  public function assignPackages($force = FALSE);

}
