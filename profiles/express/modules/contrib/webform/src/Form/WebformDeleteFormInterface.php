<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\ConfirmFormInterface;

/**
 * Defines an interface for webform delete forms.
 */
interface WebformDeleteFormInterface extends ConfirmFormInterface {

  /**
   * Returns warning message to display.
   *
   * @return array
   *   A renderable array containing a warning message.
   */
  public function getWarning();

  /**
   * {@inheritdoc}
   */
  public function getDescription();

  /**
   * Returns details to display.
   *
   * @return array
   *   A renderable array containing details.
   */
  public function getDetails();

  /**
   * Returns confirm input to display.
   *
   * @return array
   *   A renderable array containing confirm input.
   */
  public function getConfirmInput();

}
