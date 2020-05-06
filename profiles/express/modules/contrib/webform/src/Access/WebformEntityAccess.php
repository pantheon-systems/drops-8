<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;

/**
 * Defines the custom access control handler for the webform entities.
 */
class WebformEntityAccess {

  /**
   * Check whether the webform has drafts.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   The source entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkDraftsAccess(WebformInterface $webform, EntityInterface $source_entity = NULL) {
    $draft = $webform->getSetting('draft');
    switch ($draft) {
      case WebformInterface::DRAFT_AUTHENTICATED:
        $access_result = AccessResult::allowedIf(\Drupal::currentUser()->isAuthenticated());
        break;

      case WebformInterface::DRAFT_ALL:
        $access_result = AccessResult::allowed();
        break;

      case WebformInterface::DRAFT_NONE:
      default:
        $access_result = AccessResult::forbidden();
        break;
    }
    return $access_result->addCacheableDependency($webform);
  }

  /**
   * Check whether the webform has results.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   The source entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkResultsAccess(WebformInterface $webform, EntityInterface $source_entity = NULL) {
    // If results are not disabled return neutral.
    if (!$webform->getSetting('results_disabled')) {
      $access_result = AccessResult::allowed();
    }
    // If webform has any results return neutral.
    elseif (\Drupal::entityTypeManager()->getStorage('webform_submission')->getTotal($webform, $source_entity)) {
      $access_result = AccessResult::allowed();
    }
    // Finally, forbid access to the results.
    else {
      $access_result = AccessResult::forbidden();
    }
    return $access_result->addCacheableDependency($webform);
  }

  /**
   * Check whether the webform has log.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   The source entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkLogAccess(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL) {
    // ISSUE:
    // Devel routes do not use 'webform' parameter which throws the below error.
    // Some mandatory parameters are missing ("webform") to generate a URL for
    // route "entity.webform_submission.canonical"
    //
    // WORKAROUND:
    // Make sure webform parameter is set for all routes.
    // @see webform_menu_local_tasks_alter()
    if (!$webform && $webform_submission = \Drupal::routeMatch()->getParameter('webform_submission')) {
      $webform = $webform_submission->getWebform();
    }

    if (!$webform->hasSubmissionLog()) {
      $access_result = AccessResult::forbidden()->addCacheableDependency($webform);
    }
    else {
      $access_result = static::checkResultsAccess($webform, $source_entity);
    }
    return $access_result->addCacheTags(['config:webform.settings']);
  }

  /**
   * Check whether a webform setting is set to specified value.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $setting
   *   A webform setting.
   * @param string $value
   *   The setting value used to determine access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformSettingValue(WebformInterface $webform = NULL, $setting = NULL, $value = NULL) {
    return AccessResult::allowedIf($webform->getSetting($setting) === $value)
      ->addCacheableDependency($webform);
  }

}
