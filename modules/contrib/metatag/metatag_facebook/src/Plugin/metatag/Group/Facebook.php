<?php

namespace Drupal\metatag_facebook\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The Facebook group.
 *
 * @MetatagGroup(
 *   id = "facebook",
 *   label = @Translation("facebook"),
 *   description = @Translation("A set of meta tags specially for controlling advanced functionality with <a href=':fb'>Facebook</a>.", arguments = { ":fb" = "https://www.facebook.com/" }),
 *   weight = 4
 * )
 */
class Facebook extends GroupBase {
  // Inherits everything from Base.
}
