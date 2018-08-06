<?php
/**
 * @file
 * Contains \Drupal\metatag_mobile\Plugin\metatag\Tag\MsapplicationTask.
 */

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'msapplication:task' meta tag.
 *
 * @MetatagTag(
 *   id = "msapplication_task",
 *   label = @Translation("MSApplication - Task"),
 *   description = @Translation("A semi-colon -separated string defining the 'jump' list task. Should contain the 'name=' value to specify the task's name, the 'action-uri=' value to set the URL to load when the jump list is clicked, the 'icon-uri=' value to set the URL to an icon file to be displayed, and 'window-type=' set to either 'tab' (default), 'self' or 'window' to control how the link opens in the browser."),
 *   name = "msapplication-task",
 *   group = "windows_mobile",
 *   weight = 106,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MsapplicationTask extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
