<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Web App title for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "apple_mobile_web_app_title",
 *   label = @Translation("Apple Web App Title"),
 *   description = @Translation("Overrides the long site title when using the Apple Add to Home Screen."),
 *   name = "apple-mobile-web-app-title",
 *   group = "apple_mobile",
 *   weight = 89,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleMobileWebAppTitle extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
