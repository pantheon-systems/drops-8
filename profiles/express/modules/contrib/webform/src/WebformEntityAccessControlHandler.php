<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the webform entity type.
 *
 * @see \Drupal\webform\Entity\Webform.
 */
class WebformEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission('create webform')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    else {
      return parent::checkCreateAccess($account, $context, $entity_bundle);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform\WebformInterface $entity */
    // Check 'view' using 'create' custom webform submission access rules.
    // Viewing a webform is the same as creating a webform submission.
    if ($operation == 'view') {
      return AccessResult::allowed();
    }

    $uid = $entity->getOwnerId();
    $is_owner = ($account->isAuthenticated() && $account->id() == $uid);
    // Check if 'update' or 'delete' of 'own' or 'any' webform is allowed.
    if ($account->isAuthenticated()) {
      switch ($operation) {
        case 'update':
          if ($account->hasPermission('edit any webform') || ($account->hasPermission('edit own webform') && $is_owner)) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

        case 'duplicate':
          if ($account->hasPermission('create webform') && ($entity->isTemplate() || ($account->hasPermission('edit any webform') || ($account->hasPermission('edit own webform') && $is_owner)))) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

        case 'delete':
          if ($account->hasPermission('delete any webform') || ($account->hasPermission('delete own webform') && $is_owner)) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;
      }
    }

    // Check submission_* operation.
    if (strpos($operation, 'submission_') === 0) {
      // Allow users with 'view any webform submission' to view all submissions.
      if ($operation == 'submission_view_any' && $account->hasPermission('view any webform submission')) {
        return AccessResult::allowed();
      }

      // Allow users with 'view own webform submission' to view own submissions.
      if ($operation == 'submission_view_own' && $account->hasPermission('view own webform submission')) {
        return AccessResult::allowed();
      }

      // Allow (secure) token to bypass submission page and create access controls.
      if (in_array($operation, ['submission_page', 'submission_create'])) {
        $token = \Drupal::request()->query->get('token');
        if ($token && $entity->isOpen()) {
          /** @var \Drupal\webform\WebformRequestInterface $request_handler */
          $request_handler = \Drupal::service('webform.request');
          /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
          $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

          $source_entity = $request_handler->getCurrentSourceEntity('webform');
          if ($submission_storage->loadFromToken($token, $entity, $source_entity)) {
            return AccessResult::allowed();
          }
        }
      }

      // Completely block access to a template if the user can't create new
      // Webforms.
      if ($operation == 'submission_page' && $entity->isTemplate() && !$entity->access('create')) {
        return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
      }

      // Check custom webform submission access rules.
      if ($this->checkAccess($entity, 'update', $account)->isAllowed() || $entity->checkAccessRules(str_replace('submission_', '', $operation), $account)) {
        return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
      }
    }

    $access_result = parent::checkAccess($entity, $operation, $account);
    // Make sure the webform is added as a cache dependency.
    $access_result->addCacheableDependency($entity);
    return $access_result;
  }

}
