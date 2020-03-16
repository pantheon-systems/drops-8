<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "references" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_references",
 *   label = @Translation("References"),
 *   description = @Translation("A related resource that is referenced, cited, or otherwise pointed to by the described resource."),
 *   name = "dcterms.references",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class References extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
