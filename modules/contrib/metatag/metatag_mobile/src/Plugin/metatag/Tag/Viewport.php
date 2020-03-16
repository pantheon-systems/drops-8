<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Viewport for Mobile metatag.
 *
 * @MetatagTag(
 *   id = "viewport",
 *   label = @Translation("Viewport"),
 *   description = @Translation("Used by most contemporary browsers to control the display for mobile browsers. Please read a guide on responsive web design for details of what values to use."),
 *   name = "viewport",
 *   group = "mobile",
 *   weight = 84,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Viewport extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
