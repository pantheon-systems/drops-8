<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'application:name' meta tag.
 *
 * @MetatagTag(
 *   id = "application_name",
 *   label = @Translation("Application name"),
 *   description = @Translation("The default name displayed with the pinned sites tile (or icon). Set the content attribute to the desired name."),
 *   name = "application-name",
 *   group = "windows_mobile",
 *   weight = 94,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ApplicationName extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
