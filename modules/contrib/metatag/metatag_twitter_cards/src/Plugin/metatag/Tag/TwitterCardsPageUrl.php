<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards site's page url metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_page_url",
 *   label = @Translation("Page URL"),
 *   description = @Translation("The permalink / canonical URL of the current page."),
 *   name = "twitter:url",
 *   group = "twitter_cards",
 *   weight = 6,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsPageUrl extends MetaNameBase {
}
