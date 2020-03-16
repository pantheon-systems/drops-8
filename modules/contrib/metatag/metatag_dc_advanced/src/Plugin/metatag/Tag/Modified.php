<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "modified" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_modified",
 *   label = @Translation("Date Modified"),
 *   description = @Translation("Date on which the resource was changed."),
 *   name = "dcterms.modified",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Modified extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
