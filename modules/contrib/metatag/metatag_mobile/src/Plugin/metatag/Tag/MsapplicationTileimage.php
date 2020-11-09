<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:tileimage' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_tileimage",
 *   label = @Translation("MSApplication - Tile image"),
 *   description = @Translation("The URL to an image to use as the background for the live tile."),
 *   name = "msapplication-tileimage",
 *   group = "windows_mobile",
 *   weight = 109,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationTileimage extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
