<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:allowDomainMetaTags' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_allowDomainMetaTags",
 *   label = @Translation("MSApplication - Allow domain meta tags"),
 *   description = @Translation("Allows tasks to be defined on child domains of the fully qualified domain name associated with the pinned site. Should be either 'true' or 'false'."),
 *   name = "msapplication-allowDomainMetaTags",
 *   group = "windows_mobile",
 *   weight = 96,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationAllowDomainMetaTags extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
