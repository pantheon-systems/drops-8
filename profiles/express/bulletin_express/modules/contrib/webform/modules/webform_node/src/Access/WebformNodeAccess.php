<?php

namespace Drupal\webform_node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Access\WebformAccess;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the custom access control handler for the webform node.
 */
class WebformNodeAccess {

  /**
   * Check whether the user can access a node's webform results.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformResultsAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    $access_result = self::checkAccess($operation, $entity_access, $node, NULL, $account);
    if ($access_result->isAllowed()) {
      $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($node);
      return WebformAccess::checkResultsAccess($node->$webform_field_name->entity, $node);
    }
    else {
      return $access_result;
    }
  }

  /**
   * Check whether the user can access a node's webform log.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformLogAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    $access_result = self::checkWebformResultsAccess($operation, $entity_access, $node, $account);
    if (!$access_result->isAllowed()) {
      return $access_result;
    }

    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($node);
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $node->$webform_field_name->entity;
    if (!$webform->hasSubmissionLog()) {
      $access_result = AccessResult::forbidden();
    }

    return $access_result->addCacheableDependency($webform)->addCacheTags(['config:webform.settings']);
  }

  /**
   * Check whether the user can access a node's webform.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    return self::checkAccess($operation, $entity_access, $node, NULL, $account);
  }

  /**
   * Check whether the user can access a node's webform submission.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param bool $disable_pages
   *   Flag to disable pages for the current route.
   * @param bool $resend
   *   Flag to check resend email access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformSubmissionAccess($operation, $entity_access, NodeInterface $node, WebformSubmissionInterface $webform_submission, AccountInterface $account, $disable_pages = FALSE, $resend = FALSE) {
    $access_result = self::checkAccess($operation, $entity_access, $node, $webform_submission, $account);
    if ($access_result->isForbidden()) {
      return $access_result;
    }

    if ($disable_pages) {
      return WebformAccess::checkWebformWizardPagesAccess($webform_submission->getWebform());
    }

    if ($resend) {
      return WebformAccess::checkEmailAccess($webform_submission, $account);
    }

    return $access_result;
  }

  /**
   * Check whether the user can access a node's webform and/or submission.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected static function checkAccess($operation, $entity_access, NodeInterface $node, WebformSubmissionInterface $webform_submission = NULL, AccountInterface $account = NULL) {
    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($node);
    // Check that the node has a valid webform reference.
    if (!$webform_field_name || !$node->$webform_field_name->entity) {
      return AccessResult::forbidden();
    }

    // Check that the webform submission was created via the webform node.
    if ($webform_submission && $webform_submission->getSourceEntity() != $node) {
      return AccessResult::forbidden();
    }

    // Check the node operation.
    if ($operation && $node->access($operation, $account)) {
      return AccessResult::allowed();
    }

    // Check entity access.
    if ($entity_access) {
      // Check entity access for the webform.
      if (strpos($entity_access, 'webform.') === 0
        && $node->$webform_field_name->entity->access(str_replace('webform.', '', $entity_access), $account)) {
        return AccessResult::allowed();
      }
      // Check entity access for the webform submission.
      if (strpos($entity_access, 'webform_submission.') === 0
        && $webform_submission->access(str_replace('webform_submission.', '', $entity_access), $account)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}
