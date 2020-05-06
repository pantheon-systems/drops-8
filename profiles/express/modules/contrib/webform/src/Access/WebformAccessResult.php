<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Value object indicating an allowed access result, with cacheability metadata.
 */
class WebformAccessResult {

  /**
   * Creates an allowed or neutral access result.
   *
   * @param bool $condition
   *   The condition to evaluate.
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission.
   * @param bool $cache_per_user
   *   Cache per user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isAllowed() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public static function allowedIf($condition, EntityInterface $webform_entity = NULL, $cache_per_user = FALSE) {
    return $condition ? static::allowed($webform_entity, $cache_per_user) : static::neutral($webform_entity, $cache_per_user);
  }

  /**
   * Creates an AccessResultInterface object with isAllowed() === TRUE.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission.
   * @param bool $cache_per_user
   *   Cache per user.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed
   *   isAllowed() will be TRUE.
   */
  public static function allowed(EntityInterface $webform_entity = NULL, $cache_per_user = FALSE) {
    return static::addDependencies(AccessResult::allowed(), $webform_entity, $cache_per_user);
  }

  /**
   * Creates an AccessResultInterface object with isNeutral() === TRUE.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission.
   * @param bool $cache_per_user
   *   Cache per user.
   *
   * @return \Drupal\Core\Access\AccessResultForbidden
   *   isNeutral() will be TRUE.
   */
  public static function neutral(EntityInterface $webform_entity = NULL, $cache_per_user = FALSE) {
    return static::addDependencies(AccessResult::neutral(), $webform_entity, $cache_per_user);
  }

  /**
   * Creates an AccessResultInterface object with isForbidden() === TRUE.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission.
   * @param bool $cache_per_user
   *   Cache per user.
   *
   * @return \Drupal\Core\Access\AccessResultForbidden
   *   isForbidden() will be TRUE.
   */
  public static function forbidden(EntityInterface $webform_entity = NULL, $cache_per_user = FALSE) {
    return static::addDependencies(AccessResult::forbidden(), $webform_entity, $cache_per_user);
  }

  /**
   * Adds dependencies to an access result.
   *
   * @param \Drupal\Core\Access\AccessResult $access_result
   *   The access result.
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission.
   * @param bool $cache_per_user
   *   Cache per user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result with dependencies.
   */
  public static function addDependencies(AccessResult $access_result, EntityInterface $webform_entity = NULL, $cache_per_user = FALSE) {
    $access_result->cachePerPermissions();

    if ($cache_per_user) {
      $access_result->cachePerUser();
    }

    if ($webform_entity) {
      if ($webform_entity instanceof WebformSubmissionInterface) {
        $access_result->addCacheableDependency($webform_entity->getWebform());
      }
      $access_result->addCacheableDependency($webform_entity);
    }

    return $access_result;
  }

}
