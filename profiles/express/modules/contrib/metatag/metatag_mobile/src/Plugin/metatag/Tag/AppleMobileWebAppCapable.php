<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\AppleMobileWebAppCapable.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Web App Capable for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "apple_mobile_web_app_capable",
 *   label = @Translation("Web app capable?"),
 *   description = @Translation("If set to 'yes', the application will run in full-screen mode; the default behavior is to use Safari to display web content."),
 *   name = "apple-mobile-web-app-capable",
 *   group = "apple_mobile",
 *   weight = 87,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleMobileWebAppCapable extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
