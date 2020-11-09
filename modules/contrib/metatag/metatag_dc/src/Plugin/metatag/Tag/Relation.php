<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Relation" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_relation",
 *   label = @Translation("Relation"),
 *   description = @Translation("A related resource. Recommended best practice is to identify the related resource by means of a string conforming to a formal identification system."),
 *   name = "dcterms.relation",
 *   group = "dublin_core",
 *   weight = 13,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Relation extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
