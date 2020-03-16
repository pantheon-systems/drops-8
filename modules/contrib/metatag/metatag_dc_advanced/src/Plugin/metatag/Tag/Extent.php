<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "extent" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_extent",
 *   label = @Translation("Extent"),
 *   description = @Translation("The size or duration of the resource."),
 *   name = "dcterms.extent",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Extent extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
