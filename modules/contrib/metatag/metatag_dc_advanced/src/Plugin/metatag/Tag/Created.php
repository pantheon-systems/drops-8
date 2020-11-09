<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "created" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_created",
 *   label = @Translation("Date Created"),
 *   description = @Translation("Date of creation of the resource."),
 *   name = "dcterms.created",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Created extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
