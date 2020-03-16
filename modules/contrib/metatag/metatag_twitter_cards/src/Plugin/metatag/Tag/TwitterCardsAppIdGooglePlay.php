<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards app id for Google Play metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_app_id_googleplay",
 *   label = @Translation("Google Play app ID"),
 *   description = @Translation("Your app ID in the Google Play Store (i.e. ""com.android.app"")."),
 *   name = "twitter:app:id:googleplay",
 *   group = "twitter_cards",
 *   weight = 307,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsAppIdGooglePlay extends MetaNameBase {
}
