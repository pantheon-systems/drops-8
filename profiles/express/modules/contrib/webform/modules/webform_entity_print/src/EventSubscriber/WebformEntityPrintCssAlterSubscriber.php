<?php

namespace Drupal\webform_entity_print\EventSubscriber;

use Drupal\entity_print\Event\PrintCssAlterEvent;
use Drupal\entity_print\Event\PrintEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter entity print css.
 *
 * @see \Drupal\entity_print\Asset\AssetCollector::getCssLibraries
 */
class WebformEntityPrintCssAlterSubscriber implements EventSubscriberInterface {

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\entity_print\Event\PrintCssAlterEvent $event
   *   Entity Print CSS alter event.
   */
  public function alterCss(PrintCssAlterEvent $event) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    $entities = $event->getEntities();
    foreach ($entities as $entity) {
      if ($entity->getEntityTypeId() === 'webform_submission') {
        $event->getBuild()['#attached']['library'][] = 'webform_entity_print/webform_entity_print';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PrintEvents::CSS_ALTER => 'alterCss',
    ];
  }

}
