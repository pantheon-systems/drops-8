<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Generator" meta tag.
 *
 * @MetatagTag(
 *   id = "generator",
 *   label = @Translation("Generator"),
 *   description = @Translation("Describes the name and version number of the software or publishing tool used to create the page."),
 *   name = "generator",
 *   group = "advanced",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Generator extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
