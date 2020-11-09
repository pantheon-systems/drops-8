<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Theme color for Mobile metatag.
 *
 * @MetatagTag(
 *   id = "theme_color",
 *   label = @Translation("Theme Color"),
 *   description = @Translation("A color in hexidecimal format, e.g. '#0000ff' for blue; must include the '#' symbol. Used by some browsers to control the background color of the toolbar, the color used with an icon, etc."),
 *   name = "theme-color",
 *   group = "mobile",
 *   weight = 81,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ThemeColor extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
