<?php

namespace Drupal\media_entity;

/**
 * Generic Plugin exception class to be thrown when no more specific class
 * is applicable.
 */
class MediaTypeException extends \Exception {

  /**
   * Form element name that this exception belongs to.
   *
   * @var string
   */
  protected $element;

  /**
   * Construct the exception.
   *
   * @param string $element
   *   [optional] Name of form element that exception refers to.
   * @param string $message
   *   [optional] The Exception message to throw.
   * @param int $code
   *   [optional] The Exception code.
   * @param \Exception $previous
   *   [optional] The previous exception used for the exception chaining.
   */
  public function __construct($element = NULL, $message = "", $code = 0, \Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->element = $element;
  }

  /**
   * Gets element.
   *
   * @return string
   *   Element name.
   */
  public function getElement() {
    return $this->element;
  }

}
