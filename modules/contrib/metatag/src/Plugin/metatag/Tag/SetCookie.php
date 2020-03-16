<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'set-cookie' meta tag.
 *
 * @MetatagTag(
 *   id = "set_cookie",
 *   label = @Translation("Set cookie"),
 *   description = @Translation("<a href='https://www.metatags.org/meta_http_equiv_set_cookie'>Sets a cookie</a> on the visitor's browser. Can be in either NAME=VALUE format, or a more verbose format including the path and expiration date; see the link for full details on the syntax."),
 *   name = "set-cookie",
 *   group = "advanced",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SetCookie extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
