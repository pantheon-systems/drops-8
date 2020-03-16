<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Web URL meta tag.
 *
 * @MetatagTag(
 *   id = "al_web_url",
 *   label = @Translation("Web URL"),
 *   description = @Translation("The web URL; defaults to the URL for the content that contains this tag."),
 *   name = "al:web:url",
 *   group = "app_links",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlWebUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
