<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:task:separator' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_task_separator",
 *   label = @Translation("MSApplication - Task separator"),
 *   description = @Translation(""),
 *   name = "msapplication-task-separator",
 *   group = "windows_mobile",
 *   weight = 107,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationTaskSeparator extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
