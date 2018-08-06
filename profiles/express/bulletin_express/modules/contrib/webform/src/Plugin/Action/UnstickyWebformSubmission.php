<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Makes a webform submission unsticky.
 *
 * @Action(
 *   id = "webform_submission_make_unsticky_action",
 *   label = @Translation("Unstar/unflag selected submission"),
 *   type = "webform_submission"
 * )
 */
class UnstickyWebformSubmission extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $entity->setSticky(FALSE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\webform\WebformSubmissionInterface $object */
    $result = $object->sticky->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
