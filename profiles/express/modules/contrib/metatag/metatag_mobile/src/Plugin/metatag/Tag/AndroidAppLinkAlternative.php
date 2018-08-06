<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\AndroidAppLinkAlternative.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Android app link alternative for Android mobile metatag.
 *
 * @MetatagTag(
 *   id = "android_app_link_alternative",
 *   label = @Translation("Android app link alternative"),
 *   description = @Translation("A custom string for deeplinking to an Android mobile app. Should be in the format 'package_name/host_path', e.g. 'com.example.android/example/hello-screen'. The 'android-app://' prefix will be included automatically."),
 *   name = "alternate",
 *   group = "android_mobile",
 *   weight = 91,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AndroidAppLinkAlternative extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
