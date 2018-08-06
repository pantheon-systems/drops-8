<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'norton-safeweb-site-verification' meta tag.
 *
 * @MetatagTag(
 *   id = "norton_safe_web",
 *   label = @Translation("Norton Safe Web"),
 *   description = @Translation("A string provided by <a href=':norton'>Norton Safe Web</a>, full details are available from the <a href=':verify_url'>Norton Safe Web online help</a>.", arguments = { ":norton" = "https://safeweb.norton.com/", ":verify_url" = "https://safeweb.norton.com/help/site_owners" }),
 *   name = "norton-safeweb-site-verification",
 *   group = "site_verification",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class NortonSafeWeb extends MetaNameBase {
}
