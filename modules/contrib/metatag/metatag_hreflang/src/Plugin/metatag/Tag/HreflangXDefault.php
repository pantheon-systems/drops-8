<?php

namespace Drupal\metatag_hreflang\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'hreflang_xdefault' meta tag.
 *
 * @MetatagTag(
 *   id = "hreflang_xdefault",
 *   label = @Translation("Default locale (x-default)"),
 *   description = @Translation("This should point to the version of the page that is for the main or primary locale, e.g. the original version of an article that is translated into other languages."),
 *   name = "hreflang_xdefault",
 *   group = "hreflang",
 *   weight = 0,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HreflangXDefault extends HreflangBase {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'x-default';
  }

}
