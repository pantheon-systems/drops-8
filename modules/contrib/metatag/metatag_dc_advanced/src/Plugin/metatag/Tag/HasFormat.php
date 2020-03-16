<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "hasFormat" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_has_format",
 *   label = @Translation("Has Format"),
 *   description = @Translation("A related resource that is substantially the same as the pre-existing described resource, but in another format."),
 *   name = "dcterms.hasFormat",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HasFormat extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
