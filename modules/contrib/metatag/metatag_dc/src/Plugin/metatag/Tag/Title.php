<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Title" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_title",
 *   label = @Translation("Title"),
 *   description = @Translation("The name given to the resource."),
 *   name = "dcterms.title",
 *   group = "dublin_core",
 *   weight = 1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Title extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
