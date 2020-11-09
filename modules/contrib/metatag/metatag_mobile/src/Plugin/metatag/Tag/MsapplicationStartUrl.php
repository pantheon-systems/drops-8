<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:starturl' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_starturl",
 *   label = @Translation("MSApplication - Start URL"),
 *   description = @Translation("The URL to the root page of the site."),
 *   name = "msapplication-starturl",
 *   group = "windows_mobile",
 *   weight = 105,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationStartUrl extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
