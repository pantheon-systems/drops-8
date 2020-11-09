<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Android app Activity Class meta tag.
 *
 * @MetatagTag(
 *   id = "al_android_class",
 *   label = @Translation("Android app Activity Class"),
 *   description = @Translation("A fully-qualified Activity class name for intent generation."),
 *   name = "al:android:class",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlAndroidClass extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
