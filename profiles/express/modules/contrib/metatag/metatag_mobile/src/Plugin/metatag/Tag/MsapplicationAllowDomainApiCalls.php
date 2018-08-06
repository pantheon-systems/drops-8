<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\MsapplicationAllowDomainApiCalls.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:allowDomainApiCalls' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_allowDomainApiCalls",
 *   label = @Translation("MSApplication - Allow domain API calls"),
 *   description = @Translation("Allows tasks to be defined on child domains of the fully qualified domain name associated with the pinned site. Should be either 'true' or 'false'."),
 *   name = "msapplication-allowDomainApiCalls",
 *   group = "windows_mobile",
 *   weight = 95,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationAllowDomainApiCalls extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
