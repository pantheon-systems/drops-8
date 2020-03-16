<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Abstract" meta tag.
 *
 * @MetatagTag(
 *   id = "abstract",
 *   label = @Translation("Abstract"),
 *   description = @Translation("A brief and concise summary of the page's content, preferably 150 characters or less. Where as the description meta tag may be used by search engines to display a snippet about the page in search results, the abstract tag may be used to archive a summary about the page. This meta tag is <em>no longer</em> supported by major search engines."),
 *   name = "abstract",
 *   group = "basic",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   long = TRUE,
 * )
 */
class AbstractTag extends MetaNameBase {

}
