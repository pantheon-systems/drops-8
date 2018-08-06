<?php

namespace Drupal\colorbox;

/**
 * An interface for checking if colorbox should be active.
 */
interface ActivationCheckInterface {

  /**
   * Check if colorbox should be activated for the current page.
   *
   * @return bool
   *   If colorbox should be active.
   */
  public function isActive();

}
