<?php

namespace Drupal\captcha;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface CaptchaPointInterface.
 *
 * @package Drupal\captcha
 *
 * Provides an interface defining a CaptchaPoint entity.
 */
interface CaptchaPointInterface extends ConfigEntityInterface {

  /**
   * Getter for form machine ID property.
   */
  public function getFormId();

  /**
   * Setter for label property.
   *
   * @param string $form_id
   *   Form machine ID string.
   */
  public function setFormId($form_id);

  /**
   * Getter for label property.
   *
   * @return string
   *   Label string.
   */
  public function getLabel();

  /**
   * Setter for label property.
   *
   * @param string $label
   *   Label string.
   */
  public function setLabel($label);

  /**
   * Getter for captcha type property.
   */
  public function getCaptchaType();

  /**
   * Setter for captcha type property.
   *
   * @param string|null $captcha_type
   *   Captcha type.
   */
  public function setCaptchaType($captcha_type);

}
