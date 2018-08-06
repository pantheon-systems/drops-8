<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards creator meta tag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_creator",
 *   label = @Translation("Creator's Twitter account"),
 *   description = @Translation("The @username for the content creator / author for this page, including the @ symbol."),
 *   name = "twitter:creator",
 *   group = "twitter_cards",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsCreator extends MetaNameBase {
}
