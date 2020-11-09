<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "alternative" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_alternative",
 *   label = @Translation("Alternative Title"),
 *   description = @Translation("An alternative name for the resource. The distinction between titles and alternative titles is application-specific."),
 *   name = "dcterms.alternative",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Alternative extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
