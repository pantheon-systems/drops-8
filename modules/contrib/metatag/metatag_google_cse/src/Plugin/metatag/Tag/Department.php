<?php

namespace Drupal\metatag_google_cse\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'department' meta tag.
 *
 * @MetatagTag(
 *   id = "department",
 *   label = @Translation("Department"),
 *   description = @Translation("Department tag."),
 *   name = "department",
 *   group = "google_cse",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Department extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
