<?php

namespace Drupal\video_embed_wysiwyg\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * An access check to ensure the form can be used only if the filter is enabled.
 */
class FilterInUse implements AccessInterface {

  /**
   * Check if the filter is used for the given filter.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result.
   */
  public function access(RouteMatchInterface $route) {
    $filter = $route->getParameter('filter_format');
    if (!$filter || empty($filter->filters()->get('video_embed_wysiwyg')->getConfiguration()['status'])) {
      return AccessResult::forbidden()->addCacheableDependency($filter);
    }
    return AccessResult::allowed()->addCacheableDependency($filter);
  }

}
