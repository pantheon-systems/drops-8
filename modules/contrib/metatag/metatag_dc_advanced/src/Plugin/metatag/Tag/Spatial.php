<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "spatial" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_spatial",
 *   label = @Translation("Spatial Coverage"),
 *   description = @Translation("Spatial characteristics of the resource."),
 *   name = "dcterms.spatial",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Spatial extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
