<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:notification' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_notification",
 *   label = @Translation("MSApplication - Notification"),
 *   description = @Translation("A semi-colon -separated string containing 'polling-uri=' (required), 'polling-uri2=', 'polling-uri3=', 'polling-uri4=' and 'polling-uri5=' to indicate the URLs for notifications. May also contain a 'frequency=' value to specify how often (in minutes) the URLs will be polled; limited to 30, 60, 360, 720 or 1440 (default). May also contain the value 'cycle=' to control the notifications cycle."),
 *   name = "msapplication-notification",
 *   group = "windows_mobile",
 *   weight = 100,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationNotification extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
