<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\MsapplicationConfig.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:config' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_config",
 *   label = @Translation("MSApplication - Config"),
 *   description = @Translation("Should contain the full URL to a <a href='https://msdn.microsoft.com/en-us/library/dn320426(v=vs.85).aspx'>Browser configuration schema</a> file that further controls tile customizations."),
 *   name = "msapplication-config",
 *   group = "windows_mobile",
 *   weight = 98,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationConfig extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
