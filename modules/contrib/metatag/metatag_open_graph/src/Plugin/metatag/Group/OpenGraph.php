<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The open graph group.
 *
 * @MetatagGroup(
 *   id = "open_graph",
 *   label = @Translation("Open Graph"),
 *   description = @Translation("The <a href='https://ogp.me/'>Open Graph meta tags</a> are used to control how Facebook, Pinterest, LinkedIn and other social networking sites interpret the site's content."),
 *   weight = 3
 * )
 */
class OpenGraph extends GroupBase {
  // Inherits everything from Base.
}
