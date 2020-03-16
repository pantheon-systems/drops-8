<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "requires" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_requires",
 *   label = @Translation("Requires"),
 *   description = @Translation("A related resource that is required by the described resource to support its function, delivery, or coherence."),
 *   name = "dcterms.requires",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Requires extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
