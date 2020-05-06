<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\webform\WebformInterface;

/**
 * Defines the custom access control variant for the webform variants.
 */
class WebformVariantAccess {

  /**
   * Check whether the webform variant settings is enabled.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkVariantSettingsAccess(WebformInterface $webform) {
    return static::checkVariantAccess($webform);
  }

  /**
   * Check whether the webform variant create is enabled.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $webform_variant
   *   A webform variant id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkVariantCreateAccess(WebformInterface $webform, $webform_variant) {
    return static::checkVariantAccess($webform, $webform_variant);
  }

  /**
   * Check whether the webform variant is enabled.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string|null $webform_variant
   *   A webform variant id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected static function checkVariantAccess(WebformInterface $webform, $webform_variant = NULL) {
    if (!$webform->hasVariants()) {
      $access_result = AccessResult::forbidden();
    }
    else {
      /** @var \Drupal\webform\Plugin\WebformVariantManagerInterface $variant_manager */
      $variant_manager = \Drupal::service('plugin.manager.webform.variant');
      $variant_definitions = $variant_manager->getDefinitions();
      $variant_definitions = $variant_manager->removeExcludeDefinitions($variant_definitions);
      if ($webform_variant) {
        $access_result = AccessResult::allowedIf(!empty($variant_definitions[$webform_variant]));
      }
      else {
        unset($variant_definitions['broken']);
        $access_result = AccessResult::allowedIf(!empty($variant_definitions));
      }
    }

    return $access_result->addCacheTags(['config:webform.settings']);
  }

}
