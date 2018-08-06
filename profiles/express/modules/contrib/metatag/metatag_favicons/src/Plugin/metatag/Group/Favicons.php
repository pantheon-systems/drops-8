<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Group;

use \Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Provides a plugin for the 'Favicons & touch icons' meta tag group.
 *
 * @MetatagGroup(
 *   id = "favicons",
 *   label = @Translation("Favicons & touch icons"),
 *   description = @Translation("Meta tags for displaying favicons of various sizes and types. All values should be either absolute or relative URLs. No effects are added to the ""precomposed"" icons."),
 *   weight = 0,
 * )
 */
class Favicons extends GroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
