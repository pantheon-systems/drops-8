<?php

namespace Drupal\webform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a webform variant annotation object.
 *
 * Plugin Namespace: Plugin\WebformVariant.
 *
 * For a working example, see
 * \Drupal\webform\Plugin\WebformVariant\OverrideWebformVariant
 *
 * @see hook_webform_variant_info_alter()
 * @see \Drupal\webform\Plugin\WebformVariantInterface
 * @see \Drupal\webform\Plugin\WebformVariantBase
 * @see \Drupal\webform\Plugin\WebformVariantManager
 * @see plugin_api
 *
 * @Annotation
 */
class WebformVariant extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the webform variant.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The category in the admin UI where the block will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * A brief description of the webform variant.
   *
   * This will be shown when adding or configuring this webform variant.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
