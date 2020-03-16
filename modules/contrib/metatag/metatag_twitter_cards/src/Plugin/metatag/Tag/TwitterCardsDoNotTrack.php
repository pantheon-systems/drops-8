<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards DNT option.
 *
 * @MetatagTag(
 *   id = "twitter_cards_donottrack",
 *   label = @Translation("Do Not Track"),
 *   description = @Translation("By default Twitter tracks visitors when a tweet is embedded on a page using the official APIs. Setting this to 'on' will <a href=':url'>stop Twitter from tracking visitors</a>.", arguments = { ":url" = "https://dev.twitter.com/web/overview/privacy#what-privacy-options-do-website-publishers-have" }),
 *   name = "twitter:dnt",
 *   group = "twitter_cards",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsDoNotTrack extends MetaNameBase {
}
