<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "isRequiredBy" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_is_required_by",
 *   label = @Translation("Is Required By"),
 *   description = @Translation("A related resource that requires the described resource to support its function, delivery, or coherence."),
 *   name = "dcterms.isRequiredBy",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsRequiredBy extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
