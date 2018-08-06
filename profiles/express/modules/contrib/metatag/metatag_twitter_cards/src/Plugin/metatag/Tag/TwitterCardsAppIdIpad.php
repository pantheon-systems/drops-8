<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards app id for ipad metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_app_id_ipad",
 *   label = @Translation("iPad app ID"),
 *   description = @Translation("String value, should be the numeric representation of your iPad app's ID in the App Store."),
 *   name = "twitter:app:id:ipad",
 *   group = "twitter_cards",
 *   weight = 304,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsAppIdIpad extends MetaNameBase {
}
