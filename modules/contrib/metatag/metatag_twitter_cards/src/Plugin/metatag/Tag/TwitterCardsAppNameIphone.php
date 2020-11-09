<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards app name for iphone metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_app_name_iphone",
 *   label = @Translation("iPhone app name"),
 *   description = @Translation("The name of the iPhone app."),
 *   name = "twitter:app:name:iphone",
 *   group = "twitter_cards",
 *   weight = 301,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsAppNameIphone extends MetaNameBase {
}
