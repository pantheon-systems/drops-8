<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards app id for iphone metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_app_id_iphone",
 *   label = @Translation("iPhone app ID"),
 *   description = @Translation("String value, should be the numeric representation of your iPhone app's ID in the App Store."),
 *   name = "twitter:app:id:iphone",
 *   group = "twitter_cards",
 *   weight = 302,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsAppIdIphone extends MetaNameBase {
}
