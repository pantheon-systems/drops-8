<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Date" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_format",
 *   label = @Translation("Format"),
 *   description = @Translation("The file format, physical medium, or dimensions of the resource. Examples of dimensions include size and duration. Recommended best practice is to use a controlled vocabulary such as the list of Internet Media Types [MIME]."),
 *   name = "dcterms.format",
 *   group = "dublin_core",
 *   weight = 9,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Format extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
