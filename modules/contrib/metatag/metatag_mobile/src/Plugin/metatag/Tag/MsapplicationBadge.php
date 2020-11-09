<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:badge' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_badge",
 *   label = @Translation("MSApplication - Badge"),
 *   description = @Translation("A semi-colon -separated string that must contain the 'polling-uri=' value with the full URL to a <a href='https://go.microsoft.com/fwlink/p/?LinkID=314019'>Badge Schema XML file</a>. May also contain 'frequency=' value set to either 30, 60, 360, 720 or 1440 (default) which specifies (in minutes) how often the URL should be polled."),
 *   name = "msapplication-badge",
 *   group = "windows_mobile",
 *   weight = 97,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationBadge extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
