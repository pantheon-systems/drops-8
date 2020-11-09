<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'pocket-site-verification' meta tag.
 *
 * @MetatagTag(
 *   id = "pocket",
 *   label = @Translation("Pocket"),
 *   description = @Translation("A string provided by <a href=':pocket'>Pocket</a>, full details are available from the <a href=':verify_url'>Pocket online help</a>.", arguments = { ":pocket" = "https://getpocket.com/", ":verify_url" = "https://getpocket.com/publisher/verify_meta" }),
 *   name = "pocket-site-verification",
 *   group = "site_verification",
 *   weight = 7,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Pocket extends MetaNameBase {
}
