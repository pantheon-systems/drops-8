<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Provides a plugin for the 'App Links' meta tag group.
 *
 * @MetatagGroup(
 *   id = "app_links",
 *   label = @Translation("App Links"),
 *   description = @Translation("Meta tags used to expose App Links for app deep linking. See <a href="":url"">applinks.org</a> for details and documentation.", arguments = { ":url" = "http://applinks.org"}),
 *   weight = 0,
 * )
 */
class AppLinks extends GroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
