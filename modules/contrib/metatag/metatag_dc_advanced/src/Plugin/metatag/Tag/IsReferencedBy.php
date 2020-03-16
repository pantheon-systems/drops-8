<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "isReferencedBy" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_is_referenced_by",
 *   label = @Translation("Is Referenced By"),
 *   description = @Translation("A related resource that references, cites, or otherwise points to the described resource."),
 *   name = "dcterms.isReferencedBy",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsReferencedBy extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
