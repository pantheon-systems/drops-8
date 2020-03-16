<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards image width metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_image_width",
 *   label = @Translation("Image width"),
 *   description = @Translation("The width of the image being linked to, in pixels."),
 *   name = "twitter:image:width",
 *   group = "twitter_cards",
 *   weight = 7,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsImageWidth extends MetaNameBase {
}
