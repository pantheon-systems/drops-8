<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:square150x150logo' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_square150x150logo",
 *   label = @Translation("MSApplication - Square logo, 150px x 150px"),
 *   description = @Translation("The URL to a logo file that is 150px by 150px."),
 *   name = "msapplication-square150x150logo",
 *   group = "windows_mobile",
 *   weight = 101,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationSquare150x150logo extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
