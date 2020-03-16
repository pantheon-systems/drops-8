<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks Web Should Fallback meta tag.
 *
 * @MetatagTag(
 *   id = "al_web_should_fallback",
 *   label = @Translation("Should fallback"),
 *   description = @Translation("Indicates if the web URL should be used as a fallback; defaults to ""true""."),
 *   name = "al:web:should_fallback",
 *   group = "app_links",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlWebShouldFallback extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
