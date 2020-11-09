<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "isFormatOf" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_is_format_of",
 *   label = @Translation("Is Format Of"),
 *   description = @Translation("A related resource that is substantially the same as the described resource, but in another format."),
 *   name = "dcterms.isFormatOf",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsFormatOf extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
