<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Type" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_type",
 *   label = @Translation("Type"),
 *   description = @Translation("The nature or genre of the resource. Recommended best practice is to use a controlled vocabulary such as the DCMI Type Vocabulary [DCMITYPE]. To describe the file format, physical medium, or dimensions of the resource, use the Format element."),
 *   name = "dcterms.type",
 *   group = "dublin_core",
 *   weight = 8,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Type extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
