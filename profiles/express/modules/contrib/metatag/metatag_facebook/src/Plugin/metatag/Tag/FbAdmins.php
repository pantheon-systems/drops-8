<?php

namespace Drupal\metatag_facebook\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Facebook "fb:admins" meta tag.
 *
 * @MetatagTag(
 *   id = "fb_admins",
 *   label = @Translation("Facebook Admins"),
 *   description = @Translation("A comma-separated list of Facebook user IDs of people who are considered administrators or moderators of this page."),
 *   name = "fb:admins",
 *   group = "facebook",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class FbAdmins extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
