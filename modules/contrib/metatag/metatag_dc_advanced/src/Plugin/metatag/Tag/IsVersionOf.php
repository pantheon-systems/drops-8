<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "isVersionOf" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_is_version_of",
 *   label = @Translation("Is Version Of"),
 *   description = @Translation("A related resource of which the described resource is a version, edition, or adaptation. Changes in version imply substantive changes in content rather than differences in format."),
 *   name = "dcterms.isVersionOf",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsVersionOf extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
