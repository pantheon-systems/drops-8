<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msvalidate.01' meta tag.
 *
 * @MetatagTag(
 *   id = "bing",
 *   label = @Translation("Bing"),
 *   description = @Translation("A string provided by <a href=':bing'>Bing</a>, full details are available from the <a href=':verify_url'>Bing online help</a>.", arguments = { ":bing" = "http://www.bing.com/", ":verify_url" = "http://www.bing.com/webmaster/help/how-to-verify-ownership-of-your-site-afcfefc6" }),
 *   name = "msvalidate.01",
 *   group = "site_verification",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Bing extends MetaNameBase {
}
