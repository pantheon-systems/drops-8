<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;

/**
 * Defines the custom access control handler for the webform handlers.
 */
class WebformHandlerAccess {

  /**
   * Check whether the webform handler is enabled.
   *
   * @param string $webform_handler
   *   A webform handler id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkHandlerAccess($webform_handler = NULL) {
    /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
    $handler_manager = \Drupal::service('plugin.manager.webform.handler');
    $handler_definitions = $handler_manager->getDefinitions();
    $handler_definitions = $handler_manager->removeExcludeDefinitions($handler_definitions);
    if ($webform_handler) {
      $access_result = AccessResult::allowedIf(!empty($handler_definitions[$webform_handler]));
    }
    else {
      unset($handler_definitions['broken'], $handler_definitions['email']);
      $access_result = AccessResult::allowedIf(!empty($handler_definitions));
    }
    return $access_result->addCacheTags(['config:webform.settings']);
  }

}
