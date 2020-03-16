<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards site's account metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_site",
 *   label = @Translation("Site's Twitter account"),
 *   description = @Translation("The @username for the website, which will be displayed in the Card's footer; must include the @ symbol."),
 *   name = "twitter:site",
 *   group = "twitter_cards",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsSite extends MetaNameBase {
}
