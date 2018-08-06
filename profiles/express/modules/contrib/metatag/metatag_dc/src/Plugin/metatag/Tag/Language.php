<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Language" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_language",
 *   label = @Translation("Language"),
 *   description = @Translation("A language of the resource. Recommended best practice is to use a controlled vocabulary such as RFC 4646 [RFC4646]."),
 *   name = "dcterms.language",
 *   group = "dublin_core",
 *   weight = 12,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Language extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
