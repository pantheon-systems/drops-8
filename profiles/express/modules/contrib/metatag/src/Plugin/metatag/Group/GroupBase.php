<?php

/**
 * Each group will extend this base.
 */

namespace Drupal\metatag\Plugin\metatag\Group;

use Drupal\Component\Plugin\PluginBase;

abstract class GroupBase extends PluginBase {

  /**
   * Machine name of the meta tag group plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * The name of the group.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * Description of the group.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set the properties from the annotation.
    // @TODO: Should we have setProperty() methods for each of these?
    $this->id = $plugin_definition['id'];
    $this->label = $plugin_definition['label'];
    $this->description = $plugin_definition['description'];
  }

  public function id() {
    return $this->id;
  }

  public function label() {
    return $this->label;
  }

  public function description() {
    return $this->description;
  }

  /**
   * @return bool
   *   Whether this group has been enabled.
   */
  public function isActive() {
    return TRUE;
  }

}
