<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards gallery image0 metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_gallery_image0",
 *   label = @Translation("1st gallery image"),
 *   description = @Translation("A URL to the image representing the first photo in your gallery. This will be able to extract the URL from an image field."),
 *   name = "twitter:gallery:image0",
 *   group = "twitter_cards",
 *   weight = 200,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsGalleryImage0 extends MetaNameBase {
}
