<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Access\WebformAccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the access control handler for the webform submission entity type.
 *
 * @see \Drupal\webform\Entity\WebformSubmission.
 */
class WebformSubmissionAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Webform access rules manager service.
   *
   * @var \Drupal\webform\WebformAccessRulesManagerInterface
   */
  protected $accessRulesManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * WebformSubmissionAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager
   *   Webform access rules manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeInterface $entity_type, WebformAccessRulesManagerInterface $access_rules_manager, RequestStack $request_stack) {
    parent::__construct($entity_type);

    $this->accessRulesManager = $access_rules_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('webform.access_rules_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    // Check 'administer webform' permission.
    if ($account->hasPermission('administer webform')) {
      return WebformAccessResult::allowed();
    }

    // Check 'administer webform submission' permission.
    if ($account->hasPermission('administer webform submission')) {
      return WebformAccessResult::allowed();
    }

    // Check webform 'update' permission.
    if ($entity->getWebform()->access('update', $account)) {
      return WebformAccessResult::allowed($entity, TRUE);
    }

    // Check view operation token access.
    if ($operation === 'view'
      && $entity->getWebform()->getSetting('token_view')) {
      $token = $this->request->query->get('token');
      if ($token === $entity->getToken()) {
        return WebformAccessResult::allowed($entity)
          ->addCacheContexts(['url.query_args:token']);
      }

    }

    // Check 'any' or 'own' webform submission permissions.
    $operations = [
      'view' => 'view',
      'update' => 'edit',
      'delete' => 'delete',
    ];
    if (isset($operations[$operation])) {
      $action = $operations[$operation];
      // Check operation any.
      if ($account->hasPermission("$action any webform submission")) {
        return WebformAccessResult::allowed();
      }
      // Check operation own.
      if ($account->hasPermission("$action own webform submission") && $entity->isOwner($account)) {
        return WebformAccessResult::allowed($entity, TRUE);
      }
    }

    // Check other operations.
    switch ($operation) {
      case 'duplicate':
        // Check for 'create' or 'update' access.
        return WebformAccessResult::allowedIf($entity->access('create', $account) || $entity->access('update', $account));

      case 'resend':
        // Check for 'update any submission' access.
        return WebformAccessResult::allowedIf($entity->getWebform()->access('submission_update_any', $account));
    }

    // Check webform access rules.
    $webform_access = $this->accessRulesManager->checkWebformSubmissionAccess($operation, $account, $entity);
    if ($webform_access->isAllowed()) {
      return $webform_access;
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
