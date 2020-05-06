<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides an interface for Webform Access Group storage.
 */
interface WebformAccessGroupStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Load webform access groups by their related entity references.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) The webform that the submission token is associated with.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   * @param string $type
   *   (optional) Webform access type.
   *
   * @return \Drupal\webform\WebformSubmissionInterface[]
   *   An array of webform access group objects indexed by their ids.
   */
  public function loadByEntities(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $type = NULL);

  /**
   * Get source entities associated with a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   * @param string|null $entity_type
   *   Source entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Get source entities associated with a user account.
   */
  public function getUserEntities(AccountInterface $account, $entity_type = NULL);

}
