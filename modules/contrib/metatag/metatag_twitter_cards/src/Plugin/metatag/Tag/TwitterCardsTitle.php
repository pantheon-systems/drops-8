<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards title metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_title",
 *   label = @Translation("Title"),
 *   description = @Translation("The page's title, which should be concise; it will be truncated at 70 characters by Twitter. This field is required unless this the 'type' field is set to 'photo'."),
 *   name = "twitter:title",
 *   group = "twitter_cards",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsTitle extends MetaNameBase {
}
