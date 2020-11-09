<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:actor:role' meta tag.
 *
 * @MetatagTag(
 *   id = "video_actor_role",
 *   label = @Translation("Actor's role"),
 *   description = @Translation("The roles of the actor(s)."),
 *   name = "video:actor:role",
 *   group = "open_graph",
 *   weight = 46,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class VideoActorRole extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
