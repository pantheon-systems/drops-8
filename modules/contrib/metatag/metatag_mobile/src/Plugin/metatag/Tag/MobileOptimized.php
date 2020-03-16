<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Mobile optimized for Mobile metatag.
 *
 * @MetatagTag(
 *   id = "mobileoptimized",
 *   label = @Translation("Mobile Optimized"),
 *   description = @Translation("Using the value 'width' tells certain mobile Internet Explorer browsers to display as-is, without being resized. Alternatively a numerical width may be used to indicate the desired page width the page should be rendered in: '240' is the suggested default, '176' for older browsers or '480' for newer devices with high DPI screens."),
 *   name = "MobileOptimized",
 *   group = "mobile",
 *   weight = 82,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class MobileOptimized extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
