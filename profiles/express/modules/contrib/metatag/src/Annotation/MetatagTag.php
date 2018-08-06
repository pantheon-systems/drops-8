<?php

namespace Drupal\metatag\Annotation;

use Drupal\Component\Annotation\Plugin;


/**
 * Defines a MetatagTag annotation object.
 *
 * @Annotation
 */
class MetatagTag extends Plugin {

  /**
   * The meta tag plugin's internal ID, in machine name format.
   *
   * @var string
   */
  public $id;

  /**
   * The display label/name of the meta tag plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A longer explanation of what the field is for.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Proper name of the actual meta tag itself.
   *
   * @var string
   */
  public $name;

  /**
   * The group this meta tag fits in, corresponds to a MetatagGroup plugin.
   *
   * @var string
   */
  public $group;

  /**
   * Weight of the tag.
   *
   * @var int
   */
  public $weight;

  /**
   * Type of the meta tag should be either 'date', 'image', 'integer', 'label',
   * 'string' or 'uri'.
   *
   * @var string
   */
  public $type;

  /**
   * True if URL must use HTTPS.
   *
   * @var boolean
   */
  protected $secure;

  /**
   * True if more than one is allowed.
   *
   * @var boolean
   */
  public $multiple;

}
