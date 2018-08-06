<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks iOS app name meta tag.
 *
 * @MetatagTag(
 *   id = "al_ios_app_name",
 *   label = @Translation("iOS app name"),
 *   description = @Translation("The name of the app (suitable for display)"),
 *   name = "al:ios:app_name",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlIosAppName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
