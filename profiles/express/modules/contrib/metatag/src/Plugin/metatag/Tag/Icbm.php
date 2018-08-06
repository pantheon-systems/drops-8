<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'icbm' meta tag.
 *
 * @MetatagTag(
 *   id = "icbm",
 *   label = @Translation("ICBM"),
 *   description = @Translation("Geo-spatial information in 'latitude, longitude' format, e.g. '50.167958, -97.133185'; <a href='https://en.wikipedia.org/wiki/ICBM_address'>see Wikipedia for details</a>."),
 *   name = "icbm",
 *   group = "advanced",
 *   weight = 0,
 *   type = "label",
 *   secure = 0,
 *   multiple = FALSE
 * )
 */
class Icbm extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
