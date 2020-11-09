<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:window' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_window",
 *   label = @Translation("MSApplication - Window"),
 *   description = @Translation("A semi-colon -separated value that controls the dimensions of the initial window. Should contain the values 'width=' and 'height=' to control the width and height respectively."),
 *   name = "msapplication-window",
 *   group = "windows_mobile",
 *   weight = 111,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationWindow extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
