<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The iOS App link alternative for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "ios_app_link_alternative",
 *   label = @Translation("iOS app link alternative"),
 *   description = @Translation("A custom string for deeplinking to an iOS mobile app. Should be in the format 'itunes_id/scheme/host_path', e.g. 123456/example/hello-screen'. The 'ios-app://' prefix will be included automatically."),
 *   name = "alternate",
 *   group = "apple_mobile",
 *   weight = 91,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IosAppLinkAlternative extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
