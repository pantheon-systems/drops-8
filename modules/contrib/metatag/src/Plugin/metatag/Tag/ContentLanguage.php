<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The advanced "Content Language" meta tag.
 *
 * @MetatagTag(
 *   id = "content_language",
 *   label = @Translation("Content Language"),
 *   description = @Translation("Used to define this page's language code. May be the two letter language code, e.g. ""de"" for German, or the two letter code with a dash and the two letter ISO country code, e.g. ""de-AT"" for German in Austria. Still used by Bing."),
 *   name = "content-language",
 *   group = "advanced",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ContentLanguage extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
