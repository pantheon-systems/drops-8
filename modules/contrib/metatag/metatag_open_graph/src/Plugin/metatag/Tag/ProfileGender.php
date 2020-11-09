<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'profile:gender' meta tag.
 *
 * @MetatagTag(
 *   id = "profile_gender",
 *   label = @Translation("Gender"),
 *   description = @Translation("Any of Facebook's gender values should be allowed, the initial two being 'male' and 'female'."),
 *   name = "profile:gender",
 *   group = "open_graph",
 *   weight = 44,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProfileGender extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
