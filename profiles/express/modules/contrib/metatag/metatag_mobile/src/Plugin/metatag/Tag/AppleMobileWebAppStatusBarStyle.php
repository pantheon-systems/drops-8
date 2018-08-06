<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\AppleMobileWebAppStatusBarStyle.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Web App status bar style for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "apple_mobile_web_app_status_bar_style",
 *   label = @Translation("Status bar color"),
 *   description = @Translation("Requires the 'Web app capable' meta tag to be set to 'yes'. May be set to 'default', 'black', or 'black-translucent'."),
 *   name = "apple-mobile-web-app-status-bar-style",
 *   group = "apple_mobile",
 *   weight = 88,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleMobileWebAppStatusBarStyle extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
