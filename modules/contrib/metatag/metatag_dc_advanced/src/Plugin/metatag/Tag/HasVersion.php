<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "hasVersion" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_has_version",
 *   label = @Translation("Has Version"),
 *   description = @Translation("A related resource that is a version, edition, or adaptation of the described resource."),
 *   name = "dcterms.hasVersion",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HasVersion extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
