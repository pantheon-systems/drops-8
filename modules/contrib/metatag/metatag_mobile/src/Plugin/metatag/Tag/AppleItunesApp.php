<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Itunes App for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "apple_itunes_app",
 *   label = @Translation("iTunes App details"),
 *   description = @Translation("This informs iOS devices to display a banner to a specific app. If used, it must provide the 'app-id' value, the 'affiliate-data' and 'app-argument' values are optional."),
 *   name = "apple-itunes-app",
 *   group = "apple_mobile",
 *   weight = 86,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleItunesApp extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
