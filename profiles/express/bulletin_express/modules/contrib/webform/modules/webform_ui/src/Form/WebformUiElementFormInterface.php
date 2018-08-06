<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Provides an interface for webform element webform.
 */
interface WebformUiElementFormInterface extends FormInterface, ContainerInjectionInterface {

  /**
   * Is new element.
   *
   * @return bool
   *   TRUE if this webform generating a new element.
   */
  public function isNew();

  /**
   * Return the webform associated with this form.
   *
   * @return \Drupal\webform\WebformInterface
   *   A form
   */
  public function getWebform();

  /**
   * Return the webform element associated with this form.
   *
   * @return \Drupal\webform\WebformElementInterface
   *   A webform element.
   */
  public function getWebformElement();

  /**
   * Return the render element associated with this form.
   *
   * @return array
   *   An element.
   */
  public function getElement();

  /**
   * Return the render element's key associated with this form.
   *
   * @return string
   *   The render element's key.
   */
  public function getKey();

  /**
   * Return the render element's parent key associated with this form.
   *
   * @return string
   *   The render element's parent key.
   */
  public function getParentKey();

}
