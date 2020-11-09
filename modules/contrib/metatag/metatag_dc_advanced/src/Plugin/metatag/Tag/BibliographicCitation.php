<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "bibliographicCitation" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_bibliographic_citation",
 *   label = @Translation("Bibliographic Citation"),
 *   description = @Translation("A bibliographic reference for the resource. Recommended practice is to include sufficient bibliographic detail to identify the resource as unambiguously as possible."),
 *   name = "dcterms.bibliographicCitation",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class BibliographicCitation extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
