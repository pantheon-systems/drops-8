<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaHttpEquivBase;

/**
 * The Cleartype for Mobile metatag.
 *
 * @MetatagTag(
 *   id = "cleartype",
 *   label = @Translation("Cleartype"),
 *   description = @Translation("A legacy meta tag for older versions of Internet Explorer on Windows, use the value 'on' to enable it; this tag is ignored by all other browsers."),
 *   name = "cleartype",
 *   group = "mobile",
 *   weight = 85,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Cleartype extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
