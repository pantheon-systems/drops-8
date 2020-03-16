<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Windows Phone app name meta tag.
 *
 * @MetatagTag(
 *   id = "al_windows_phone_app_name",
 *   label = @Translation("Windows Phone app name"),
 *   description = @Translation("The name of the app (suitable for display)"),
 *   name = "al:windows_phone:app_name",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlWindowsPhoneAppName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
