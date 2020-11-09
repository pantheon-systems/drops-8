<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The Cache Control meta tag.
 *
 * @MetatagTag(
 *   id = "cache_control",
 *   label = @Translation("Cache control"),
 *   description = @Translation("Used to control whether a browser caches a specific page locally. Not commonly used. Should be used in conjunction with the Pragma meta tag."),
 *   name = "cache-control",
 *   group = "advanced",
 *   weight = 10,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CacheControl extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
