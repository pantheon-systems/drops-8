<?php

/**
 * @file
 * Document all supported APIs.
 */

/**
 * Provides a ability to integrate alternative routes with metatags.
 *
 * Return an entity when the given route/route parameters matches a certain
 * entity. All meta tags will be rendered on that page.
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
 * Alter the meta tags for pages that are not of content entities.
 *
 * @param array $metatags
 *   The special meta tags to be added to the page.
 * @param array $context
 *   The context for the current meta tags being generated. Will contain the
 *   following:
 *   'entity' - The entity being processed; passed by reference.
 */
function hook_metatags_alter(array &$metatags, array &$context) {
  // Exclude meta tags on frontpage.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $metatags = NULL;
  }
}

/**
 * Alter the meta tags for any page prior to page attachment.
 *
 * @param array $metatag_attachments
 *   An array of metatag objects to be attached to the current page.
 */
function hook_metatags_attachments_alter(array &$metatag_attachments) {
  if (\Drupal::service('path.matcher')->isFrontPage() && \Drupal::currentUser()->isAnonymous()) {
    foreach ($metatag_attachments['#attached']['html_head'] as $id => $attachment) {
      if ($attachment[1] == 'title') {
        $metatag_attachments['#attached']['html_head'][$id][0]['#attributes']['content'] = 'Front Page Title for Anonymous Users';
      }
    }
  }
}

/**
 * Allow the list of Metatag D7's tags to be changed.
 *
 * This is only used when migrating meta tags from Metatag-D7.
 *
 * @param array $tags_map
 *   An array of D7 tag names mapped against the D8 tag's IDs.
 */
function hook_metatag_migrate_metatagd7_tags_map_alter(array $tags_map) {
  // This tag was renamed in D8.
  $tags_map['custom:tag'] = 'custom_tag';
}
