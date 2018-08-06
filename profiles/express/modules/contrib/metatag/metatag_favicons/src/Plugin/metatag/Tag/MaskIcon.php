<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Favicons "mask-icon" meta tag.
 *
 * @MetatagTag(
 *   id = "mask-icon",
 *   label = @Translation("Icon: SVG"),
 *   description = @Translation("A grayscale scalable vector graphic (SVG) file."),
 *   name = "mask-icon",
 *   group = "favicons",
 *   weight = 2,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MaskIcon extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}

