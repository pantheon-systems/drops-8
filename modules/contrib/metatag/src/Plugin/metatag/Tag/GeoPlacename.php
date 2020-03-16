<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'geo.placename' meta tag.
 *
 * @MetatagTag(
 *   id = "geo_placename",
 *   label = @Translation("Geographical place name"),
 *   description = @Translation("A location's formal name."),
 *   name = "geo.placename",
 *   group = "advanced",
 *   weight = 0,
 *   type = "label",
 *   secure = 0,
 *   multiple = FALSE
 * )
 */
class GeoPlacename extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
