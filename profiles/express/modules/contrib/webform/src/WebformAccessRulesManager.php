<?php

namespace Drupal\webform;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Access\WebformAccessResult;

/**
 * The webform access rules manager service.
 */
class WebformAccessRulesManager implements WebformAccessRulesManagerInterface {

  use StringTranslationTrait;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * WebformAccessRulesManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function checkWebformAccess($operation, AccountInterface $account, WebformInterface $webform) {
    $access_rules = $this->getAccessRules($webform);
    $cache_per_user = $this->cachePerUser($access_rules);

    $condition = $this->checkAccessRules($operation, $account, $access_rules);
    return WebformAccessResult::allowedIf($condition, $webform, $cache_per_user);
  }

  /**
   * {@inheritdoc}
   */
  public function checkWebformSubmissionAccess($operation, AccountInterface $account, WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();

    $access_rules = $this->getAccessRules($webform);
    $cache_per_user = $this->cachePerUser($access_rules);

    // Check operation.
    if ($this->checkAccessRules($operation, $account, $access_rules)) {
      return WebformAccessResult::allowed($webform_submission, $cache_per_user);
    }

    // Check *_own operation.
    if ($webform_submission->isOwner($account)
      && isset($access_rules[$operation . '_own'])
      && $this->checkAccessRule($access_rules[$operation . '_own'], $account)) {
      return WebformAccessResult::allowed($webform_submission, $cache_per_user);
    }

    // Check *_any operation.
    if (isset($access_rules[$operation . '_any'])
      && $this->checkAccessRule($access_rules[$operation . '_any'], $account)) {
      return WebformAccessResult::allowed($webform_submission, $cache_per_user);
    }

    return WebformAccessResult::neutral($webform_submission, $cache_per_user);
  }

  /****************************************************************************/
  // Get access rules methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getDefaultAccessRules() {
    $access_rules = [];

    foreach ($this->getAccessRulesInfo() as $access_rule => $info) {
      $access_rules[$access_rule] = [
        'roles' => $info['roles'],
        'users' => $info['users'],
        'permissions' => $info['permissions'],
      ];
    }

    return $access_rules;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRulesInfo() {
    $access_rules = $this->moduleHandler->invokeAll('webform_access_rules');
    $this->moduleHandler->alter('webform_access_rules', $access_rules);

    // Set access rule default values.
    foreach ($access_rules as $access_rule => $info) {
      $access_rules[$access_rule] += [
        'title' => NULL,
        'description' => NULL,
        'weight' => 0,
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ];
    }

    uasort($access_rules, [SortArray::class, 'sortByWeightElement']);

    return $access_rules;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRules(WebformInterface $webform) {
    return $webform->getAccessRules() + $this->getDefaultAccessRules();
  }

  /****************************************************************************/
  // Check access rules methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, AccountInterface $account, array $access_rules) {
    // Check administer access rule and grant full access to user.
    if ($this->checkAccessRule($access_rules['administer'], $account)) {
      return TRUE;
    }

    // Check operation.
    if (isset($access_rules[$operation])
      && $this->checkAccessRule($access_rules[$operation], $account)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks an access rule against a user account's roles and id.
   *
   * @param array $access_rule
   *   An access rule.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   Returns a TRUE if access is allowed.
   *
   * @see \Drupal\webform\Plugin\WebformElementBase::checkAccessRule
   */
  protected function checkAccessRule(array $access_rule, AccountInterface $account) {
    if (!empty($access_rule['roles']) && array_intersect($access_rule['roles'], $account->getRoles())) {
      return TRUE;
    }
    elseif (!empty($access_rule['users']) && in_array($account->id(), $access_rule['users'])) {
      return TRUE;
    }
    elseif (!empty($access_rule['permissions'])) {
      foreach ($access_rule['permissions'] as $permission) {
        if ($account->hasPermission($permission)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function cachePerUser(array $access_rules) {
    foreach ($access_rules as $access_rule) {
      if (!empty($access_rule['users'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
