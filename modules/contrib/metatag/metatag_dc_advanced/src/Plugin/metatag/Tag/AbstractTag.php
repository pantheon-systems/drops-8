<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Abstract" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_abstract",
 *   label = @Translation("Abstract"),
 *   description = @Translation("A summary of the resource."),
 *   name = "dcterms.abstract",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AbstractTag extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
