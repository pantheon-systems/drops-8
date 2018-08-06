<?php

namespace Drupal\responsive_preview\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\AdminContext;

/**
 * Defines the RouteCacheContext service for "per admin route" caching.
 *
 * Cache context ID: 'route.is_admin'.
 */
class RouteAdminCacheContext implements CacheContextInterface {

  /**
   * The route admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $routeAdminContext;

  /**
   * Constructs a new RouteCacheContext class.
   *
   * @param \Drupal\Core\Routing\AdminContext $route_admin_context
   *   The route admin context service.
   */
  public function __construct(AdminContext $route_admin_context) {
    $this->routeAdminContext = $route_admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Route is admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->routeAdminContext->isAdminRoute() ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
