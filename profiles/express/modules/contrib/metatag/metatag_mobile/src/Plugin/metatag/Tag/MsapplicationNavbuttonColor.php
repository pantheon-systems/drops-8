<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\MsapplicationNavbuttonColor.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:navbutton:color' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_navbutton_color",
 *   label = @Translation("MSApplication - Nav button color"),
 *   description = @Translation("Controls the color of the Back and Forward buttons in the pinned site browser window."),
 *   name = "msapplication-navbutton-color",
 *   group = "windows_mobile",
 *   weight = 99,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationNavbuttonColor extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
