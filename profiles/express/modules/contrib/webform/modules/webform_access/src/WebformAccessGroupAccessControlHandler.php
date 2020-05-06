<?php

namespace Drupal\webform_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the webform access entity type.
 *
 * @see \Drupal\webform_access\Entity\WebformAccessGroup.
 */
class WebformAccessGroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface $entity */
    if ($account->hasPermission('administer webform')) {
      $access_result = AccessResult::allowed();
    }
    elseif ($operation === 'update') {
      $admin_ids = $entity->getAdminIds();
      $is_admin = ($admin_ids && in_array($account->id(), $admin_ids)) ? TRUE : FALSE;
      $access_result = AccessResult::allowedIf($is_admin)
        ->addCacheableDependency($entity);
    }
    else {
      $access_result = AccessResult::neutral();
    }

    return $access_result->addCacheableDependency($account);
  }

}
