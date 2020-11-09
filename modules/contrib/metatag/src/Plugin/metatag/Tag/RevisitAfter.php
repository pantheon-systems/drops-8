<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Rating" meta tag.
 *
 * @MetatagTag(
 *   id = "revisit_after",
 *   label = @Translation("Revisit After"),
 *   description = @Translation("Tell search engines when to index the page again. Very few search engines support this tag, it is more useful to use an <a href='https://www.drupal.org/project/xmlsitemap'>XML Sitemap</a> file."),
 *   name = "revisit-after",
 *   group = "advanced",
 *   weight = 8,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class RevisitAfter extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
