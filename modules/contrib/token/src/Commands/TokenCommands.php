<?php

namespace Drupal\token\Commands;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * TokenCommands provides the Drush hook implementation for cache clears.
 */
class TokenCommands extends DrushCommands {

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * TokenCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Adds a cache clear option for tokens.
   *
   * @param array $types
   *   The Drush clear types to make available.
   * @param bool $includeBootstrappedTypes
   *   Whether to include types only available in a bootstrapped Drupal or not.
   *
   * @hook on-event cache-clear
   */
  public function cacheClear(array &$types, $includeBootstrappedTypes) {
    if (!$includeBootstrappedTypes || !$this->moduleHandler->moduleExists('token')) {
      return;
    }

    $types['token'] = 'token_clear_cache';
  }

}
