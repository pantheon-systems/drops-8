<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "available" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_available",
 *   label = @Translation("Date Available"),
 *   description = @Translation("Date (often a range) that the resource became or will become available."),
 *   name = "dcterms.available",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Available extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
