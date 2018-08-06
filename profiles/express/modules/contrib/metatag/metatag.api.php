<?php

/**
 * @file
 * Document all supported APIs.
 */

/**
 * Provides a ability to integrate alternative routes with metatags.
 *
 * Return an entity when the given route/route parameters matches a certain
 * entity. All metatags will be rendered on that page.
 *
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The route match.
 *
 * @return \Drupal\Core\Entity\EntityInterface|null
 *   Return an entity, if the route should use metatags.
 */
function hook_metatag_route_entity(\Drupal\Core\Routing\RouteMatchInterface $route_match) {
  if ($route_match->getRouteName() === 'example.test_route') {
    if ($node = $route_match->getParameter('node')) {
      return $node;
    }
  }
}

/**
 * Alter the metatags for pages that are not of content entities.
 *
 * @param array $metatags
 *   The special metatags to be added to the page.
 * @param array $context
 *   The context, containing the entity used for token replacements.
 */
function hook_metatags_alter(array &$metatags, array $context) {
  // Exclude metatags on frontpage.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $metatags = NULL;
  }
}
