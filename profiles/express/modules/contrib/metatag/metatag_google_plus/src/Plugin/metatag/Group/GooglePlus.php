<?php

namespace Drupal\metatag_google_plus\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The GooglePlus group.
 *
 * @MetatagGroup(
 *   id = "google_plus",
 *   label = @Translation("Google Plus"),
 *   description = @Translation("A set of meta tags specially for controlling the summaries displayed when content is shared on <a href=':plus'>Google Plus</a>.", arguments = { ":plus" = "https://plus.google.com/" }),
 *   weight = 4
 * )
 */
class GooglePlus extends GroupBase {
  // Inherits everything from Base.
}
