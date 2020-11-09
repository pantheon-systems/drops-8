<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'profile:username' meta tag.
 *
 * @MetatagTag(
 *   id = "profile_username",
 *   label = @Translation("Username"),
 *   description = @Translation("A pseudonym / alias of this person."),
 *   name = "profile:username",
 *   group = "open_graph",
 *   weight = 45,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProfileUsername extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
