<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "valid" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_valid",
 *   label = @Translation("Date Valid"),
 *   description = @Translation("Date (often a range) of validity of a resource."),
 *   name = "dcterms.valid",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Valid extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
