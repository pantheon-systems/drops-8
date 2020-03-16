<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards app's custom URL scheme for ipad metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_app_url_ipad",
 *   label = @Translation("iPad app's custom URL scheme"),
 *   description = @Translation("The iPad app's custom URL scheme (must include ""://"" after the scheme name)."),
 *   name = "twitter:app:url:ipad",
 *   group = "twitter_cards",
 *   weight = 305,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsAppUrlIpad extends MetaNameBase {
}
