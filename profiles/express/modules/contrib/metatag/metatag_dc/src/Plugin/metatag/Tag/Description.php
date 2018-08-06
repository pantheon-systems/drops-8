<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Description" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_description",
 *   label = @Translation("Description"),
 *   description = @Translation("An account of the resource. Description may include but is not limited to: an abstract, a table of contents, a graphical representation, or a free-text account of the resource."),
 *   name = "dcterms.description",
 *   group = "dublin_core",
 *   weight = 4,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Description extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
