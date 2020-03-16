<?php

namespace Drupal\metatag\Plugin\metatag\Group;

use Drupal\Component\Plugin\PluginBase;

/**
 * Each group will extend this base.
 */
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
    // @todo Should we have setProperty() methods for each of these?
    $this->id = $plugin_definition['id'];
    $this->label = $plugin_definition['label'];
    $this->description = $plugin_definition['description'];
  }

  /**
   * Get this group's internal ID.
   *
   * @return string
   *   This group's ID.
   */
  public function id() {
    return $this->id;
  }

  /**
   * Get this group's human-friendly name.
   *
   * @return string
   *   This group's human-friendly name.
   */
  public function label() {
    return $this->label;
  }

  /**
   * This group object's description.
   *
   * @return string
   *   This group's ID.
   */
  public function description() {
    return $this->description;
  }

  /**
   * Whether or not this group is being used.
   *
   * @return bool
   *   Whether this group has been enabled.
   */
  public function isActive() {
    return TRUE;
  }

}
