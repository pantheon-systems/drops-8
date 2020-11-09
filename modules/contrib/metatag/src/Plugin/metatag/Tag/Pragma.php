<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The Pragma meta tag.
 *
 * @MetatagTag(
 *   id = "pragma",
 *   label = @Translation("Pragma"),
 *   description = @Translation("Used to control whether a browser caches a specific page locally. Not commonly used. Should be used in conjunction with the Cache-Control meta tag."),
 *   name = "pragma",
 *   group = "advanced",
 *   weight = 12,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Pragma extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
