<?php

namespace Drupal\webform_options_custom\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform_options_custom\WebformOptionsCustomInterface;

/**
 * Defines the custom access control handler for the webform options custom entity.
 */
class WebformOptionsCustomAccess {

  /**
   * Check that webform options custom ource can be updated by a user.
   *
   * @param \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom
   *   A webform options custome entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkOptionsCustomSourceAccess(WebformOptionsCustomInterface $webform_options_custom, AccountInterface $account) {
    return $webform_options_custom->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIfHasPermission($account, 'edit webform source'));
  }

}
