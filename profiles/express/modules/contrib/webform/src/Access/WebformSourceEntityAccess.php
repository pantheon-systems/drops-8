<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the custom access control handler for the webform source entities.
 */
class WebformSourceEntityAccess {

  /**
   * Check whether the user can access an entity's webform results.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkEntityResultsAccess(EntityInterface $entity, AccountInterface $account) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    return AccessResult::allowedIf($entity->access('update', $account) && $entity_reference_manager->getWebform($entity));
  }
}

