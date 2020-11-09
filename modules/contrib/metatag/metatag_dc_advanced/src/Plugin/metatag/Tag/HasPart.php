<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "hasPart" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_has_part",
 *   label = @Translation("Has Part"),
 *   description = @Translation("A related resource that is included either physically or logically in the described resource."),
 *   name = "dcterms.hasPart",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HasPart extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
