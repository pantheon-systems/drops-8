<?php

namespace Drupal\Driver\Exception;

use Drupal\Driver\DriverInterface;

/**
 * Drupal driver manager base exception class.
 */
abstract class Exception extends \Exception {

  /**
   * The driver where the exception occurred.
   *
   * @var \Drupal\Driver\DriverInterface
   */
  private $driver;

  /**
   * Initializes Drupal driver manager exception.
   *
   * @param string $message
   *   The exception message.
   * @param \Drupal\Driver\DriverInterface $driver
   *   The driver where the exception occurred.
   * @param int $code
   *   Optional exception code. Defaults to 0.
   * @param \Exception $previous
   *   Optional previous exception that was thrown.
   */
  public function __construct($message, DriverInterface $driver = NULL, $code = 0, \Exception $previous = NULL) {
    $this->driver = $driver;

    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns exception driver.
   *
   * @return \Drupal\Driver\DriverInterface
   *   The driver where the exception occurred.
   */
  public function getDriver() {
    return $this->driver;
  }

}
