<?php

namespace Drupal\content_lock\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Class BreakLockRoutes.
 *
 * @package Drupal\content_lock\Routing
 */
class BreakLockRoutes implements ContainerInjectionInterface {

  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];

    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $definition) {
      if ($definition instanceof ContentEntityTypeInterface && $definition->getBundleEntityType()) {
        $routes['content_lock.break_lock.' . $definition->id()] = new Route(
          '/admin/break-lock/' . $definition->id() . '/{entity}',
          [
            '_form' => $definition->getHandlerClass('break_lock_form'),
            '_title' => 'Break lock',
          ],
          [
            '_custom_access' => $definition->getHandlerClass('break_lock_form') . '::access',
          ],
          [
            '_admin_route' => TRUE,
            'parameters' => [
              'entity' => [
                'type' => 'entity:' . $definition->id(),
              ],
            ],
          ]
        );
      }
    }
    return $routes;
  }

}
