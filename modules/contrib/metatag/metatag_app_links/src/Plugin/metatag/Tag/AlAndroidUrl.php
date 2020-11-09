<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Android app URL scheme meta tag.
 *
 * @MetatagTag(
 *   id = "al_android_url",
 *   label = @Translation("Android app URL scheme"),
 *   description = @Translation("A custom scheme for the Android app."),
 *   name = "al:android:url",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlAndroidUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
