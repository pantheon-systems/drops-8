<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards site's description.
 *
 * @MetatagTag(
 *   id = "twitter_cards_description",
 *   label = @Translation("Description"),
 *   description = @Translation("A description that concisely summarizes the content of the page, as appropriate for presentation within a Tweet. Do not re-use the title text as the description, or use this field to describe the general services provided by the website. The string will be truncated, by Twitter, at the word to 200 characters."),
 *   name = "twitter:description",
 *   group = "twitter_cards",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsDescription extends MetaNameBase {
}
