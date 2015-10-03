<?php

/**
 * @file
 * Contains \Drupal\node\NodeTypeAccessControlHandler.
 */

namespace Drupal\node;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the node type entity type.
 *
 * @see \Drupal\node\Entity\NodeType
 */
class NodeTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');
        break;

      case 'delete':
        if ($entity->isLocked()) {
          return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
        }
        else {
          return parent::checkAccess($entity, $operation, $langcode, $account)->cacheUntilEntityChanges($entity);
        }
        break;

      default:
        return parent::checkAccess($entity, $operation, $langcode, $account);
        break;
    }
  }

}
