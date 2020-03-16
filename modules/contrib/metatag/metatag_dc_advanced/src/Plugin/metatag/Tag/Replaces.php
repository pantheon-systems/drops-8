<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "replaces" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_replaces",
 *   label = @Translation("Replaces"),
 *   description = @Translation("A related resource that is supplanted, displaced, or superseded by the described resource."),
 *   name = "dcterms.replaces",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Replaces extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
