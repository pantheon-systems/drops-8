<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "isReplacedBy" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_is_replaced_by",
 *   label = @Translation("Is Replaced By"),
 *   description = @Translation("A related resource that supplants, displaces, or supersedes the described resource."),
 *   name = "dcterms.isReplacedBy",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsReplacedBy extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
