<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The Expires meta tag.
 *
 * @MetatagTag(
 *   id = "expires",
 *   label = @Translation("Expires"),
 *   description = @Translation("Control when the browser's internal cache of the current page should expire. The date must to be an <a href='http://www.csgnetwork.com/timerfc1123calc.html'>RFC-1123</a>-compliant date string that is represented in Greenwich Mean Time (GMT), e.g. 'Thu, 01 Sep 2016 00:12:56 GMT'. Set to '0' to stop the page being cached entirely."),
 *   name = "expires",
 *   group = "advanced",
 *   weight = 11,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Expires extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
