<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'google-site-verification' meta tag.
 *
 * @MetatagTag(
 *   id = "google",
 *   label = @Translation("Google"),
 *   description = @Translation("A string provided by <a href=':google'>Google</a>, full details are available from the <a href=':verify_url'>Google online help</a>.", arguments = { ":google" = "https://www.google.com/", ":verify_url" = "https://support.google.com/webmasters/answer/35179?hl=en" }),
 *   name = "google-site-verification",
 *   group = "site_verification",
 *   weight = 4,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class Google extends MetaNameBase {
}
