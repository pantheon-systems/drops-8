<?php

namespace Drupal\pathauto\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\pathauto\PathautoState;

/**
 * Pathauto entity update action.
 *
 * @Action(
 *   id = "pathauto_update_alias",
 *   label = @Translation("Update URL alias of an entity"),
 * )
 */
class UpdateAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->path->pathauto = PathautoState::CREATE;
    \Drupal::service('pathauto.generator')->updateEntityAlias($entity, 'bulkupdate', array('message' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'create url aliases');
    return $return_as_object ? $result : $result->isAllowed();
  }

}
