<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards image alternative text metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_image_alt",
 *   label = @Translation("Image alternative text"),
 *   description = @Translation("The alternative text of the image being linked to. Limited to 420 characters."),
 *   name = "twitter:image:alt",
 *   group = "twitter_cards",
 *   weight = 7,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsImageAlt extends MetaNameBase {
}
