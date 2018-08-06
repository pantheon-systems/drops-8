<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'geo.region' meta tag.
 *
 * @MetatagTag(
 *   id = "geo_region",
 *   label = @Translation("Geographical region"),
 *   description = @Translation("A location's two-letter international country code, with an optional two-letter region, e.g. 'US-NH' for New Hampshire in the USA."),
 *   name = "geo.region",
 *   group = "advanced",
 *   weight = 0,
 *   type = "label",
 *   secure = 0,
 *   multiple = FALSE
 * )
 */
class GeoRegion extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
