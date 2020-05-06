<?php

namespace Drupal\webform_group;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\webform\WebformAccessRulesManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform group manager manager.
 */
class WebformGroupManager implements WebformGroupManagerInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;


  /**
   * The webform access rules manager.
   *
   * @var \Drupal\webform\WebformAccessRulesManagerInterface
   */
  protected $accessRulesManager;

  /**
   * The current user's group roles.
   *
   * @var array
   */
  protected $currentGroupRoles;

  /**
   * The current request's group content.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $currentGroupContent;

  /**
   * Cache webform access rules.
   *
   * @var array
   */
  protected $accessRules = [];

  /**
   * Cache webform group allowed tokens.
   *
   * @var array
   */
  protected $alloweGroupRoleTokens;

  /**
   * Constructs a WebformGroupManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager
   *   The webform access rules manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler, WebformAccessRulesManagerInterface $access_rules_manager) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestHandler = $request_handler;
    $this->accessRulesManager = $access_rules_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupOwnerTokenEnable() {
    return $this->configFactory->get('webform_group.settings')->get('mail.group_owner') ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupRoleTokenEnabled($group_role_id) {
    $allowed_group_role_tokens = $this->getAllowedGroupRoleTokens();
    return isset($allowed_group_role_tokens[$group_role_id]);
  }

  /**
   * Get allowed token group roles.
   *
   * @return array
   *   An associative array containing allowed token group roles.
   */
  protected function getAllowedGroupRoleTokens() {
    if (!isset($this->alloweGroupRoleTokens)) {
      $allowed_group_roles = $this->configFactory->get('webform_group.settings')->get('mail.group_roles');
      $this->alloweGroupRoleTokens = ($allowed_group_roles) ? array_combine($allowed_group_roles, $allowed_group_roles) : [];
    }
    return $this->alloweGroupRoleTokens;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUserGroupRoles() {
    if (isset($this->currentGroupRoles)) {
      return $this->currentGroupRoles;
    }

    $group_content = $this->getCurrentGroupContent();
    $this->currentGroupRoles = ($group_content) ? $this->getUserGroupRoles($group_content, $this->currentUser) : [];
    return $this->currentGroupRoles;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentGroupContent() {
    if (isset($this->currentGroupContent)) {
      return $this->currentGroupContent;
    }

    $this->currentGroupContent = FALSE;

    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform_submission']);
    if (!$source_entity) {
      return $this->currentGroupContent;
    }

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
    $group_content_storage = $this->entityTypeManager->getStorage('group_content');

    // Get group content id for the source entity.
    $group_content_ids = $group_content_storage->getQuery()
      ->condition('entity_id', $source_entity->id())
      ->execute();
    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $group_content_storage->loadMultiple($group_content_ids);
    foreach ($group_contents as $group_content) {
      $group_content_entity = $group_content->getEntity();
      if ($group_content_entity->getEntityTypeId() === $source_entity->getEntityTypeId()
        && $group_content_entity->id() === $source_entity->id()
      ) {
        $this->currentGroupContent = $group_content;
        break;
      }
    }

    return $this->currentGroupContent;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmissionUserGroupRoles(WebformSubmissionInterface $webform_submission, AccountInterface $account) {
    $group_content = $this->getWebformSubmissionGroupContent($webform_submission);
    return ($group_content) ? $this->getUserGroupRoles($group_content, $account) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmissionGroupContent(WebformSubmissionInterface $webform_submission) {
    $source_entity = $webform_submission->getSourceEntity();
    if (!$source_entity) {
      return NULL;
    }

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
    $group_content_storage = $this->entityTypeManager->getStorage('group_content');

    // Get group content id for the source entity.
    $group_content_ids = $group_content_storage->getQuery()
      ->condition('entity_id', $source_entity->id())
      ->execute();

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $group_content_storage->loadMultiple($group_content_ids);
    foreach ($group_contents as $group_content) {
      $group_content_entity = $group_content->getEntity();
      if ($group_content_entity->getEntityTypeId() === $source_entity->getEntityTypeId()
        && $group_content_entity->id() === $source_entity->id()
      ) {
        return $group_content;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentGroupWebform() {
    return ($this->getCurrentGroupContent()) ? $this->requestHandler->getCurrentWebform() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRules(WebformInterface $webform) {
    $webform_id = $webform->id();
    if (isset($this->accessRules[$webform_id])) {
      return $this->accessRules[$webform_id];
    }

    $access_rules = $webform->getAccessRules()
      + $this->accessRulesManager->getDefaultAccessRules();

    // Remove configuration access rules which is never applicate the webform
    // group integration.
    unset($access_rules['configuration']);

    // Set default group roles for each permission.
    foreach ($access_rules as &$access_rule) {
      $access_rule += ['group_roles' => []];
    }

    $this->accessRules[$webform_id] = $access_rules;
    return $access_rules;
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Get current user group roles for group content.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   Group content.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return array
   *   An array of group roles for the group content.
   */
  protected function getUserGroupRoles(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $group_type_id = $group->getGroupType()->id();

    // Must get implied groups, which includes outsider, by calling
    // \Drupal\group\Entity\Storage\GroupRoleStorage::loadByUserAndGroup.
    // @see \Drupal\group\Entity\Storage\GroupRoleStorageInterface::loadByUserAndGroup
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $group_roles = $group_role_storage->loadByUserAndGroup($account, $group, TRUE);
    if (!$group_roles) {
      return [];
    }

    $group_roles = array_keys($group_roles);
    $group_roles = array_combine($group_roles, $group_roles);

    // Add global roles (i.e. member, outsider, etc...)
    foreach ($group_roles as $group_role_id) {
      if (strpos($group_role_id, $group_type_id . '-') === 0) {
        $global_role_id = str_replace($group_type_id . '-', '', $group_role_id);
        $group_roles[$global_role_id] = $global_role_id;
      }
    }

    return $group_roles;
  }

}
