<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\XUaCompatible.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaHttpEquivBase;

/**
 * Provides a plugin for the 'x:ua:compatible' meta tag.
 *
 * @MetatagTag(
 *   id = "x_ua_compatible",
 *   label = @Translation("X-UA-Compatible"),
 *   description = @Translation("Indicates to IE which rendering engine should be used for the current page."),
 *   name = "x-ua-compatible",
 *   group = "windows_mobile",
 *   weight = 93,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class XUaCompatible extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
