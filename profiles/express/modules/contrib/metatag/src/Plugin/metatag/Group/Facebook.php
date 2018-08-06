<?php

namespace Drupal\metatag\Plugin\metatag\Group;


/**
 * The basic group.
 *
 * @MetatagGroup(
 *   id = "facebook",
 *   label = @Translation("Facebook"),
 *   description = @Translation("Meta tags used to integrate with Facebook's APIs. Most sites do not need to use these, they are primarily of benefit for sites using either the Facebook widgets, the Facebook Connect single-signon system, or are using Facebook's APIs in a custom way. Sites that do need these meta tags usually will only need to set them globally."),
 *   weight = 6
 * )
 */
class Facebook extends GroupBase {
  // Inherits everything from Base.
}
