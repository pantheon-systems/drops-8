<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "license" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_license",
 *   label = @Translation("License"),
 *   description = @Translation("A legal document giving official permission to do something with the resource."),
 *   name = "dcterms.license",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class License extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
