<?php

namespace Drupal\media_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the media entity.
 */
class MediaAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer media')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($account->id() && $account->id() == $entity->getPublisherId()) ? TRUE : FALSE;
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('view media') && $entity->status->value);

      case 'update':
        return AccessResult::allowedIf(($account->hasPermission('update media') && $is_owner) || $account->hasPermission('update any media'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf(($account->hasPermission('delete media') && $is_owner) ||  $account->hasPermission('delete any media'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    // No opinion.
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create media');
  }

}
