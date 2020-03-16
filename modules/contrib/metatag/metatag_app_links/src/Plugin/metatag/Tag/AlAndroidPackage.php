<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Android app package ID meta tag.
 *
 * @MetatagTag(
 *   id = "al_android_package",
 *   label = @Translation("Android app package ID"),
 *   description = @Translation("A fully-qualified package name for intent generation. <strong>This attribute is required by the App Links specification.</strong>"),
 *   name = "al:android:package",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlAndroidPackage extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
