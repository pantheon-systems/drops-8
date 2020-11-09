<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'p:domain_verify' meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest",
 *   label = @Translation("Pinterest"),
 *   description = @Translation("A string provided by <a href=':pinterest'>Pinterest</a>, full details are available from the <a href=':verify_url'>Pinterest online help</a>.", arguments = { ":pinterest" = "https://www.pinterest.com/", ":verify_url" = "https://help.pinterest.com/en/business/article/claim-your-website" }),
 *   name = "p:domain_verify",
 *   group = "site_verification",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Pinterest extends MetaNameBase {
}
