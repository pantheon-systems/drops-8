<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards player width metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_player_width",
 *   label = @Translation("Media player width"),
 *   description = @Translation("The width of the media player iframe, in pixels. Required when using a Media player card."),
 *   name = "twitter:player:width",
 *   group = "twitter_cards",
 *   weight = 401,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * );
 */
class TwitterCardsPlayerWidth extends MetaNameBase {
}
