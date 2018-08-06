<?php

namespace Drupal\captcha\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber clearing the cached definitions when saving captcha settings.
 */
class CaptchaCachedSettingsSubscriber implements EventSubscriberInterface {

  /**
   * Clearing the cached definitions whenever the settings are modified.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    // Changing the Captcha settings means that any page might result in other
    // settings for captcha so the cached definitions need to be cleared.
    if ($event->getConfig()->getName() === 'captcha.settings') {
      \Drupal::service('element_info')->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
