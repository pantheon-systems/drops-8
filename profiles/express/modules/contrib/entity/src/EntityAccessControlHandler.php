<?php

namespace Drupal\entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Controls access based on the generic entity permissions.
 *
 * @see \Drupal\entity\UncacheableEntityPermissionProvider
 */
class EntityAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);

    if (!$entity_type->hasHandlerClass('permission_provider') || !is_a($entity_type->getHandlerClass('permission_provider'), EntityPermissionProvider::class, TRUE)) {
      throw new \Exception("This entity access control handler requires the entity permissions provider: {EntityPermissionProvider::class}");
    }
  }


  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      if ($entity instanceof EntityOwnerInterface) {
        $result = $this->checkEntityOwnerPermissions($entity, $operation, $account);
      }
      else {
        $result = $this->checkEntityPermissions($entity, $operation, $account);
      }
    }

    // Ensure that access is evaluated again when the entity changes.
    return $result->addCacheableDependency($entity);
  }

  /**
   * Checks the entity operation and bundle permissions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      $permissions = [
        "view {$entity->getEntityTypeId()}"
      ];
    }
    else {
      $permissions = [
        "$operation {$entity->getEntityTypeId()}",
        "$operation {$entity->bundle()} {$entity->getEntityTypeId()}",
      ];
    }
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

  /**
   * Checks the entity operation and bundle permissions, with owners.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityOwnerPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
        if (($account->id() == $entity->getOwnerId())) {
          $permissions = [
            "view own unpublished {$entity->getEntityTypeId()}",
          ];
          return AccessResult::allowedIfHasPermissions($account, $permissions)->cachePerUser();
        }
        return AccessResult::neutral()->cachePerUser();
      }
      else {
        return AccessResult::allowedIfHasPermissions($account, [
          "view {$entity->getEntityTypeId()}",
        ]);
      }
    }
    else {
     if (($account->id() == $entity->getOwnerId())) {
        $result = AccessResult::allowedIfHasPermissions($account, [
          "$operation own {$entity->getEntityTypeId()}",
          "$operation any {$entity->getEntityTypeId()}",
          "$operation own {$entity->bundle()} {$entity->getEntityTypeId()}",
          "$operation any {$entity->bundle()} {$entity->getEntityTypeId()}",
        ], 'OR');
      }
      else {
        $result = AccessResult::allowedIfHasPermissions($account, [
          "$operation any {$entity->getEntityTypeId()}",
          "$operation any {$entity->bundle()} {$entity->getEntityTypeId()}",
        ], 'OR');
      }
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral()) {
      $permissions = [
        'administer ' . $this->entityTypeId,
        'create ' . $this->entityTypeId,
      ];
      if ($entity_bundle) {
        $permissions[] = 'create ' . $entity_bundle . ' ' . $this->entityTypeId;
      }

      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    return $result;
  }

}
