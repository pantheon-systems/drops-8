<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Source" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_source",
 *   label = @Translation("Source"),
 *   description = @Translation("A related resource from which the described resource is derived. The described resource may be derived from the related resource in whole or in part. Recommended best practice is to identify the related resource by means of a string conforming to a formal identification system."),
 *   name = "dcterms.source",
 *   group = "dublin_core",
 *   weight = 11,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Source extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
