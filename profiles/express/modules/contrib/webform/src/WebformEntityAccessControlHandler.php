<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Access\WebformAccessResult;
use Drupal\webform\Plugin\WebformSourceEntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the access control handler for the webform entity type.
 *
 * @see \Drupal\webform\Entity\Webform.
 */
class WebformEntityAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Webform source entity plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformSourceEntityManagerInterface
   */
  protected $webformSourceEntityManager;

  /**
   * Webform access rules manager service.
   *
   * @var \Drupal\webform\WebformAccessRulesManagerInterface
   */
  protected $accessRulesManager;

  /**
   * WebformEntityAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\Plugin\WebformSourceEntityManagerInterface $webform_source_entity_manager
   *   Webform source entity plugin manager.
   * @param \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager
   *   Webform access rules manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, WebformSourceEntityManagerInterface $webform_source_entity_manager, WebformAccessRulesManagerInterface $access_rules_manager) {
    parent::__construct($entity_type);

    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->webformSourceEntityManager = $webform_source_entity_manager;
    $this->accessRulesManager = $access_rules_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.webform.source_entity'),
      $container->get('webform.access_rules_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Check 'administer webform' and 'create webform' permissions.
    if ($account->hasPermission('administer webform')) {
      return WebformAccessResult::allowed();
    }
    elseif ($account->hasPermission('create webform')) {
      return WebformAccessResult::allowed();
    }
    else {
      return WebformAccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform\WebformInterface $entity */

    // Check 'administer webform' permission.
    if ($account->hasPermission('administer webform')) {
      return WebformAccessResult::allowed();
    }

    // Check 'administer' access rule.
    if ($account->isAuthenticated()) {
      $administer_access_result = $this->accessRulesManager->checkWebformAccess('administer', $account, $entity);
      if ($administer_access_result->isAllowed()) {
        return $administer_access_result;
      }
    }

    $is_owner = ($account->id() == $entity->getOwnerId());

    // Check 'view' operation use 'submission_create' when viewing rendered
    // HTML webform or use access 'configuration' when requesting a
    // webform's configuration via REST or JSON API.
    // @see https://www.drupal.org/project/webform/issues/2956771
    if ($operation === 'view') {
      // Check is current request if for HTML.
      $is_html = ($this->requestStack->getCurrentRequest()->getRequestFormat() === 'html');
      // Make sure JSON API 1.x requests format which is 'html' is
      // detected properly.
      // @see https://www.drupal.org/project/jsonapi/issues/2877584
      $is_jsonapi = (strpos($this->requestStack->getCurrentRequest()->getPathInfo(), '/jsonapi/') === 0) ? TRUE : FALSE;
      if ($is_html && !$is_jsonapi) {
        $access_result = $this->accessRulesManager->checkWebformAccess('create', $account, $entity);
      }
      else {
        if ($account->hasPermission('access any webform configuration') || ($account->hasPermission('access own webform configuration') && $is_owner)) {
          $access_result = WebformAccessResult::allowed($entity, TRUE);
        }
        else {
          $access_result = $this->accessRulesManager->checkWebformAccess('configuration', $account, $entity);
        }
      }
      if ($access_result instanceof AccessResultReasonInterface) {
        $access_result->setReason('Access to webform configuration is required.');
      }
      return $access_result->addCacheContexts(['url.path', 'request_format']);
    }

    // Check if 'update', or 'delete' of 'own' or 'any' webform is allowed.
    if ($account->isAuthenticated()) {
      switch ($operation) {
        case 'test':
        case 'update':
          if ($account->hasPermission('edit any webform') || ($account->hasPermission('edit own webform') && $is_owner)) {
            return WebformAccessResult::allowed($entity, TRUE);
          }
          break;

        case 'duplicate':
          if ($account->hasPermission('create webform') && ($entity->isTemplate() || ($account->hasPermission('edit any webform') || ($account->hasPermission('edit own webform') && $is_owner)))) {
            return WebformAccessResult::allowed($entity, TRUE);
          }
          break;

        case 'delete':
          if ($account->hasPermission('delete any webform') || ($account->hasPermission('delete own webform') && $is_owner)) {
            return WebformAccessResult::allowed($entity, TRUE);
          }
          break;
      }
    }

    // Check webform access rules.
    $rules_access_result = $this->accessRulesManager->checkWebformAccess($operation, $account, $entity);
    if ($rules_access_result->isAllowed()) {
      return $rules_access_result;
    }

    // Check submission_* operation.
    if (strpos($operation, 'submission_') === 0) {
      // Grant user with administer webform submission access to do whatever he
      // likes on the submission operations.
      if ($account->hasPermission('administer webform submission')) {
        return WebformAccessResult::allowed();
      }

      // Allow users with 'view any webform submission' or
      // 'administer webform submission' to view all submissions.
      if ($operation === 'submission_view_any' && ($account->hasPermission('view any webform submission') || $account->hasPermission('administer webform submission'))) {
        return WebformAccessResult::allowed();
      }

      // Allow users with 'view own webform submission' to view own submissions.
      if ($operation === 'submission_view_own' && $account->hasPermission('view own webform submission')) {
        return WebformAccessResult::allowed();
      }

      // Allow users with 'edit any webform submission' to update any submissions.
      if ($operation === 'submission_update_any' && $account->hasPermission('edit any webform submission')) {
        return WebformAccessResult::allowed();
      }

      // Allow users with 'edit own webform submission' to update own submissions.
      if ($operation === 'submission_update_own' && $account->hasPermission('edit own webform submission')) {
        return WebformAccessResult::allowed();
      }

      if (in_array($operation, ['submission_page', 'submission_create'])) {
        /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
        $submission_storage = $this->entityTypeManager->getStorage('webform_submission');

        // Check limit total unique access.
        // @see \Drupal\webform\WebformSubmissionForm::setEntity
        if ($entity->getSetting('limit_total_unique')) {
          $source_entity = $this->webformSourceEntityManager->getSourceEntity('webform');
          $last_submission = $submission_storage->getLastSubmission($entity, $source_entity, NULL, ['in_draft' => FALSE]);
          if ($last_submission && $last_submission->access('update')) {
            return WebformAccessResult::allowed($last_submission);
          }
        }

        // Check limit user unique access.
        // @see \Drupal\webform\WebformSubmissionForm::setEntity
        if ($entity->getSetting('limit_user_unique')) {
          // Require user to be authenticated to access a unique submission.
          if (!$account->isAuthenticated()) {
            return WebformAccessResult::forbidden($entity);
          }
          $source_entity = $this->webformSourceEntityManager->getSourceEntity('webform');
          $last_submission = $submission_storage->getLastSubmission($entity, $source_entity, $account, ['in_draft' => FALSE]);
          if ($last_submission && $last_submission->access('update')) {
            return WebformAccessResult::allowed($last_submission);
          }
        }

        // Allow (secure) token to bypass submission page and create access controls.
        $token = $this->requestStack->getCurrentRequest()->query->get('token');
        if ($token && $entity->isOpen()) {
          $source_entity = $this->webformSourceEntityManager->getSourceEntity('webform');
          if ($submission = $submission_storage->loadFromToken($token, $entity, $source_entity)) {
            return WebformAccessResult::allowed($submission)
              ->addCacheContexts(['url']);
          }
        }
      }

      // The "page" operation is the same as "create" but requires that the
      // Webform is allowed to be displayed as dedicated page.
      // Used by the 'entity.webform.canonical' route.
      if ($operation === 'submission_page') {
        // Completely block access to a template if the user can't create new
        // Webforms.
        $create_access = $entity->access('create', $account, TRUE);
        if ($entity->isTemplate() && !$create_access->isAllowed()) {
          return WebformAccessResult::forbidden($entity)
            ->addCacheableDependency($create_access);
        }

        // Block access if the webform does not have a page URL.
        if (!$entity->getSetting('page')) {
          $source_entity = $this->webformSourceEntityManager->getSourceEntity('webform');
          if (!$source_entity) {
            return WebformAccessResult::forbidden($entity);
          }
        }
      }

      // Convert submission 'page' to corresponding 'create' access rule.
      $submission_operation = str_replace('submission_page', 'submission_create', $operation);
      // Remove 'submission_*' prefix.
      $submission_operation = str_replace('submission_', '', $submission_operation);

      // Check webform submission access rules.
      $submission_access_result = $this->accessRulesManager->checkWebformAccess($submission_operation, $account, $entity);
      if ($submission_access_result->isAllowed()) {
        return $submission_access_result;
      }

      // Check webform 'update' access.
      $update_access_result = $this->checkAccess($entity, 'update', $account);
      if ($update_access_result->isAllowed()) {
        return $update_access_result;
      }
    }

    // NOTE: Not calling parent::checkAccess().
    // @see \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
    if ($operation === 'delete' && $entity->isNew()) {
      return WebformAccessResult::forbidden($entity);
    }
    else {
      return WebformAccessResult::neutral($entity);
    }
  }

}
