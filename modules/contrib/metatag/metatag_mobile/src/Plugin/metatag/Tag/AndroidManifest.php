<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Android Manifest for Android mobile metatag.
 *
 * @MetatagTag(
 *   id = "android_manifest",
 *   label = @Translation("Manifest"),
 *   description = @Translation("A URL to a manifest.json file that describes the application. The <a href='https://developer.chrome.com/multidevice/android/installtohomescreen'>JSON-based manifest</a> provides developers with a centralized place to put metadata associated with a web application."),
 *   name = "manifest",
 *   group = "android_mobile",
 *   weight = 92,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AndroidManifest extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
