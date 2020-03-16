<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Rating" meta tag.
 *
 * @MetatagTag(
 *   id = "rating",
 *   label = @Translation("Rating"),
 *   description = @Translation("Used to rate content for audience appropriateness. This tag has little known influence on search engine rankings, but can be used by browsers, browser extentions, and apps. The <a href='https://www.metatags.org/meta_name_rating'>most common options</a> are general, mature, restricted, 14 years, safe for kids. If you follow the <a href='http://www.rtalabel.org/index.php?content=howto'>RTA Documentation</a> you should enter RTA-5042-1996-1400-1577-RTA"),
 *   name = "rating",
 *   group = "advanced",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Rating extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
