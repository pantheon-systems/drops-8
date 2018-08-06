<?php

namespace Drupal\pathauto\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\pathauto\AliasTypeManager;

/**
 * A subscriber to clear fielddefinition cache when saving pathauto settings.
 */
class PathautoSettingsCacheTag implements EventSubscriberInterface {

  protected $entityFieldManager;
  protected $aliasTypeManager;

  /**
   * Constructs a PathautoSettingsCacheTag object.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, AliasTypeManager $alias_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->aliasTypeManager = $alias_type_manager;
  }

  /**
   * Invalidate the 'rendered' cache tag whenever the settings are modified.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'pathauto.settings') {
      $config = $event->getConfig();
      $original_entity_types = $config->getOriginal('enabled_entity_types');

      // Clear cached field definitions if the values are changed.
      if ($original_entity_types != $config->get('enabled_entity_types')) {
        $this->entityFieldManager->clearCachedFieldDefinitions();
        $this->aliasTypeManager->clearCachedDefinitions();
      }
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
