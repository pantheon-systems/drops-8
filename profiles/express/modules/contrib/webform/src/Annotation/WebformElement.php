<?php

namespace Drupal\webform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a webform element annotation object.
 *
 * Plugin Namespace: Plugin\WebformElement.
 *
 * For a working example, see
 * \Drupal\webform\Plugin\WebformElement\Email
 *
 * @see hook_webform_element_info_alter()
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementManager
 * @see \Drupal\webform\Plugin\WebformElementManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class WebformElement extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * URL to the element's API documentation.
   *
   * @var string
   */
  public $api;

  /**
   * The element's module dependencies.
   *
   * @var array
   *
   * @see webform_webform_element_info_alter()
   */
  public $dependencies = [];

  /**
   * The human-readable name of the webform element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The default key used for new webform element.
   *
   * @var string
   */
  public $default_key = '';

  /**
   * The category in the admin UI where the webform will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * A brief description of the webform element.
   *
   * This will be shown when adding or configuring this webform element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * Flag that defines hidden element.
   *
   * @var bool
   */
  public $hidden = FALSE;

  /**
   * Flag that defines multiline element.
   *
   * @var bool
   */
  public $multiline = FALSE;

  /**
   * Flag that defines composite element.
   *
   * @var bool
   */
  public $composite = FALSE;

  /**
   * Flag that defines if #states wrapper should applied be to the element.
   *
   * @var bool
   */
  public $states_wrapper = FALSE;

}
