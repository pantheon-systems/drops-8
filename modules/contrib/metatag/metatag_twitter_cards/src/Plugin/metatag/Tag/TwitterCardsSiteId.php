<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards site's id metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_site_id",
 *   label = @Translation("Site's Twitter account ID"),
 *   description = @Translation("The numerical Twitter account ID for the website, which will be displayed in the Card's footer."),
 *   name = "twitter:site:id",
 *   group = "twitter_cards",
 *   weight = 3,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsSiteId extends MetaNameBase {
}
