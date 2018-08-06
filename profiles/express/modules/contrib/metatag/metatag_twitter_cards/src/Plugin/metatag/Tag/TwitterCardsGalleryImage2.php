<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards gallery image2 metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_gallery_image2",
 *   label = @Translation("3rd gallery image"),
 *   description = @Translation("A URL to the image representing the third photo in your gallery. This will be able to extract the URL from an image field."),
 *   name = "twitter:gallery:image2",
 *   group = "twitter_cards",
 *   weight = 202,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsGalleryImage2 extends MetaNameBase {
}
