<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'google' meta tag.
 *
 * @MetatagTag(
 *   id = "google",
 *   label = @Translation("Google"),
 *   description = @Translation("This meta tag communicates with Google. There are currently two directives supported: 'nositelinkssearchbox' to not to show the sitelinks search box, and 'notranslate' to ask Google not to offer a translation of the page. Both options may be added, just separate them with a comma. See <a href='https://support.google.com/webmasters/answer/79812?hl=en'>meta tags that Google understands</a> for further details."),
 *   name = "google",
 *   group = "advanced",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Google extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
