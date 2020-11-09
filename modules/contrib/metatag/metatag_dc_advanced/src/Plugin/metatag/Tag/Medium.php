<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "medium" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_medium",
 *   label = @Translation("Medium"),
 *   description = @Translation("The material or physical carrier of the resource."),
 *   name = "dcterms.medium",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Medium extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
