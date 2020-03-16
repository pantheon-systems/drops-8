<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "provenance" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_provenance",
 *   label = @Translation("Provenance"),
 *   description = @Translation("A statement of any changes in ownership and custody of the resource since its creation that are significant for its authenticity, integrity, and interpretation. The statement may include a description of any changes successive custodians made to the resource."),
 *   name = "dcterms.provenance",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Provenance extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
