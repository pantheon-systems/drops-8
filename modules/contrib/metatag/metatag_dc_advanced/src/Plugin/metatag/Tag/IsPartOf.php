<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "isPartOf" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_is_part_of",
 *   label = @Translation("Is Part Of"),
 *   description = @Translation("A related resource in which the described resource is physically or logically included."),
 *   name = "dcterms.isPartOf",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsPartOf extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
