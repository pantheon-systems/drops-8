<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:tooltip' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_tooltip",
 *   label = @Translation("MSApplication - Tooltip"),
 *   description = @Translation("Controls the text shown in the tooltip for the pinned site's shortcut."),
 *   name = "msapplication-tooltip",
 *   group = "windows_mobile",
 *   weight = 110,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationTooltip extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
