<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Description" meta tag.
 *
 * @MetatagTag(
 *   id = "og_description",
 *   label = @Translation("Description"),
 *   description = @Translation("A one to two sentence description of the content."),
 *   name = "og:description",
 *   group = "open_graph",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   long = TRUE,
 * )
 */
class OgDescription extends MetaPropertyBase {

}
