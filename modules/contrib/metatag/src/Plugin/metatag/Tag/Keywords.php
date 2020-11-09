<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Keywords" meta tag.
 *
 * @MetatagTag(
 *   id = "keywords",
 *   label = @Translation("Keywords"),
 *   description = @Translation("A comma-separated list of keywords about the page. This meta tag is <em>no longer</em> supported by most search engines."),
 *   name = "keywords",
 *   group = "basic",
 *   weight = 4,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Keywords extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
