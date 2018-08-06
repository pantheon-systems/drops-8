<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the webform submission entity type.
 *
 * @see \Drupal\webform\Entity\WebformSubmission.
 */
class WebformSubmissionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    // Check webform submission access permissions.
    // @todo: Refactor and consolidate below code after there are tests.
    switch ($operation) {
      case 'view':
        // Allow users with 'view any webform submission' to view all submissions.
        if ($account->hasPermission('view any webform submission')) {
          return AccessResult::allowed();
        }

        // Allow users with 'view own webform submission' to view own submission.
        if ($account->hasPermission('view own webform submission') && $entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed();
        }
        break;

      case 'update':
        // Allow users with 'edit any webform submission' to edit all submissions.
        if ($account->hasPermission('edit any webform submission')) {
          return AccessResult::allowed();
        }
        // Allow users with 'edit own webform submission' to edit own submission.
        if ($account->hasPermission('edit own webform submission') && $entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed();
        }
        break;

      case 'delete':
        // Allow users with 'delete any webform submission' to edit all submissions.
        if ($account->hasPermission('delete any webform submission')) {
          return AccessResult::allowed();
        }
        // Allow users with 'delete own webform submission' to edit own submission.
        if ($account->hasPermission('delete own webform submission') && $entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed();
        }
        break;
    }

    // Check webform update access.
    $webform = $entity->getWebform();
    if ($webform->checkAccessRules($operation, $account, $entity)) {
      return AccessResult::allowed();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
