<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The Pinterest group.
 *
 * @MetatagGroup(
 *   id = "pinterest",
 *   label = @Translation("Pinterest"),
 *   description = @Translation("A set of meta tags used to control how the site's content is consumed by <a href='https://pinterest.com/'>Pinterest</a>."),
 *   weight = 4
 * )
 */
class Pinterest extends GroupBase {
  // Inherits everything from Base.
}
