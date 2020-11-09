<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'profile:first_name' meta tag.
 *
 * @MetatagTag(
 *   id = "profile_first_name",
 *   label = @Translation("First name"),
 *   description = @Translation("The first name of the person who's Profile page this is."),
 *   name = "profile:first_name",
 *   group = "open_graph",
 *   weight = 42,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProfileFirstName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
