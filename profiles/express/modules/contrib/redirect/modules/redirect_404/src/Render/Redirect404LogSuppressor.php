<?php

namespace Drupal\redirect_404\Render;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Allows 'page not found' events to be suppressed by returning a NullLogger.
 */
class Redirect404LogSuppressor implements LoggerChannelFactoryInterface {
  use DependencySerializationTrait;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Redirect404LogSuppressor object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_channel_factory, ConfigFactoryInterface $config_factory) {
    $this->loggerChannelFactory = $logger_channel_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function get($channel) {
    if ($channel == 'page not found' && $this->configFactory->get('redirect_404.settings')->get('suppress_404')) {
      // Do not log if a 404 error is detected and the suppress_404 is enabled.
      return new NullLogger();
    }

    // Call LoggerChannelFactory to let the default logger workflow proceed.
    return $this->loggerChannelFactory->get($channel);
  }

  /**
   * {@inheritdoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0) {
    $this->loggerChannelFactory->addLogger($logger, $priority);
  }

}
