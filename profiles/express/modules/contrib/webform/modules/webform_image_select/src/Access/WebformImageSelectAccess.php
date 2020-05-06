<?php

namespace Drupal\webform_image_select\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform_image_select\WebformImageSelectImagesInterface;

/**
 * Defines the custom access control handler for the webform image select images entity.
 */
class WebformImageSelectAccess {

  /**
   * Check that webform image select images source can be updated by a user.
   *
   * @param \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_image_select_images
   *   A webform image select image entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkImagesSourceAccess(WebformImageSelectImagesInterface $webform_image_select_images, AccountInterface $account) {
    return $webform_image_select_images->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIfHasPermission($account, 'edit webform source'));
  }

}
