<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Defines an interface for webform third party settings manager classes.
 */
interface WebformThirdPartySettingsManagerInterface extends ThirdPartySettingsInterface {

  /**
   * Wrapper for \Drupal\Core\Extension\ModuleHandlerInterface::alter.
   *
   * Loads all webform third party settings before execute alter hooks.
   *
   * @see \Drupal\webform\WebformThirdPartySettingsManager::__construct
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL);

}
