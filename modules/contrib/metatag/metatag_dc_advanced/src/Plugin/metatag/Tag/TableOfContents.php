<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "tableOfContents" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_table_of_contents",
 *   label = @Translation("Table Of Contents"),
 *   description = @Translation("A list of subunits of the resource."),
 *   name = "dcterms.tableOfContents",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TableOfContents extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
