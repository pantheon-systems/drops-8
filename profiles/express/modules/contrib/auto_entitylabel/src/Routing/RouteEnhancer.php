<?php

/**
 * @file
 * Contains \Drupal\auto_entitylabel\Routing\RouteEnhancer.
 */

namespace Drupal\auto_entitylabel\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhances Auto Entity Label routes by adding proper information about the bundle name.
 */
class RouteEnhancer implements RouteEnhancerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a RouteEnhancer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (($bundle = $this->entityManager->getDefinition($defaults['entity_type_id'])->getBundleEntityType()) && isset($defaults[$bundle])) {
      // Auto Entity Label forms only need the actual name of the bundle they're dealing
      // with, not an upcasted entity object, so provide a simple way for them
      // to get it.
      $defaults['bundle'] = $defaults['_raw_variables']->get($bundle);
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return ($route->hasOption('auto_label'));
  }

}
