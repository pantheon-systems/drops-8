<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The TwitterCards group.
 *
 * @MetatagGroup(
 *   id = "twitter_cards",
 *   label = @Translation("Twitter Cards"),
 *   description = @Translation("A set of meta tags specially for controlling the summaries displayed when content is shared on <a href='https://twitter.com/'>Twitter</a>."),
 *   weight = 4
 * )
 */
class TwitterCards extends GroupBase {
  // Inherits everything from Base.
}
