<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the webform options entity type.
 *
 * @see \Drupal\webform\Entity\WebformOptions.
 */
class WebformOptionsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer webform'))->cachePerPermissions();
  }

}
