<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "conformsTo" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_conforms_to",
 *   label = @Translation("Conforms To"),
 *   description = @Translation("An established standard to which the described resource conforms."),
 *   name = "dcterms.conformsTo",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ConformsTo extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
