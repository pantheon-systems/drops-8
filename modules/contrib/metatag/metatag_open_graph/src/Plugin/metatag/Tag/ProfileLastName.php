<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'profile:last_name' meta tag.
 *
 * @MetatagTag(
 *   id = "profile_last_name",
 *   label = @Translation("Last name"),
 *   description = @Translation("The person's last name."),
 *   name = "profile:last_name",
 *   group = "open_graph",
 *   weight = 43,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProfileLastName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
