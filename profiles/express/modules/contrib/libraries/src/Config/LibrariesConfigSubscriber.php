<?php

namespace Drupal\libraries\Config;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration changes of the 'libraries.settings' configuration.
 */
class LibrariesConfigSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * Constructs a Libraries API configuration subscriber.
   *
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal kernel.
   */
  public function __construct(DrupalKernelInterface $kernel) {
    $this->kernel = $kernel;
  }

  /**
   * Invalidates the container when the definition settings are updated.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if (($event->getConfig()->getName() === 'libraries.settings') && $event->isChanged('definition')) {
      $this->kernel->invalidateContainer();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::SAVE => 'onConfigSave'];
  }

}
