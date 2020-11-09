<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Format Detection for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "format_detection",
 *   label = @Translation("Format detection"),
 *   description = @Translation("If set to 'telephone=no' the page will not be checked for phone numbers, which would be presented."),
 *   name = "format-detection",
 *   group = "apple_mobile",
 *   weight = 90,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class FormatDetection extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
