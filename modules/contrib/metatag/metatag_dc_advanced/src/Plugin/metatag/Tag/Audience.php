<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "audience" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_audience",
 *   label = @Translation("Audience"),
 *   description = @Translation("A class of entity for whom the resource is intended or useful."),
 *   name = "dcterms.audience",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Audience extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
