<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Android app name meta tag.
 *
 * @MetatagTag(
 *   id = "al_android_app_name",
 *   label = @Translation("Android app name"),
 *   description = @Translation("The name of the app (suitable for display)."),
 *   name = "al:android:app_name",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlAndroidAppName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
