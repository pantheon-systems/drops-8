<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Favicons "shortcut icon" meta tag.
 *
 * @MetatagTag(
 *   id = "shortcut_icon",
 *   label = @Translation("Default shortcut icon"),
 *   description = @Translation("The traditional favicon, must be either a GIF, ICO, JPG/JPEG or PNG image."),
 *   name = "shortcut icon",
 *   group = "favicons",
 *   weight = 1,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ShortcutIcon extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
