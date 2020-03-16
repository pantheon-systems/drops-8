<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "temporal" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_temporal",
 *   label = @Translation("Temporal Coverage"),
 *   description = @Translation("Temporal characteristics of the resource."),
 *   name = "dcterms.temporal",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Temporal extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
