<?php

namespace Drupal\metatag\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MetatagGroup annotation object.
 *
 * @Annotation
 */
class MetatagGroup extends Plugin {

  /**
   * The group's internal ID, in machine name format.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the group.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Description of the group.
   *
   * @var string
   */
  public $description;

  /**
   * Weight of the group.
   *
   * @var int
   */
  public $weight;

}
