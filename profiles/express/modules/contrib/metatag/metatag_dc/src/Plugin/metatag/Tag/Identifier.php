<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Identifier" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_identifier",
 *   label = @Translation("Identifier"),
 *   description = @Translation("An unambiguous reference to the resource within a given context. Recommended best practice is to identify the resource by means of a string conforming to a formal identification system."),
 *   name = "dcterms.identifier",
 *   group = "dublin_core",
 *   weight = 10,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Identifier extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
