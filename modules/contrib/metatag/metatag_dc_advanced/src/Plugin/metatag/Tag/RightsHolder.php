<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "rightsHolder" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_rights_holder",
 *   label = @Translation("Rights Holder"),
 *   description = @Translation("A person or organization owning or managing rights over the resource."),
 *   name = "dcterms.rightsHolder",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class RightsHolder extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
