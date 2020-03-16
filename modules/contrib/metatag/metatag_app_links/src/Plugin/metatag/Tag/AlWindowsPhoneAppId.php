<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Windows Phone app ID meta tag.
 *
 * @MetatagTag(
 *   id = "al_windows_phone_app_id",
 *   label = @Translation("Windows Phone app ID"),
 *   description = @Translation("The app ID for the app store."),
 *   name = "al:windows_phone:app_id",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlWindowsPhoneAppId extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
