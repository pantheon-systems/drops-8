<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "issued" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_issued",
 *   label = @Translation("Date Issued"),
 *   description = @Translation("Date of formal issuance (e.g., publication) of the resource."),
 *   name = "dcterms.issued",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Issued extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
