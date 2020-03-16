<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:tilecolor' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_tilecolor",
 *   label = @Translation("MSApplication - Tile color"),
 *   description = @Translation("The HTML color to use as the background color for the live tile."),
 *   name = "msapplication-tilecolor",
 *   group = "windows_mobile",
 *   weight = 108,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationTilecolor extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
