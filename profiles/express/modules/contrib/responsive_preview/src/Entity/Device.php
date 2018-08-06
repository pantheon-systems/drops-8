<?php

namespace Drupal\responsive_preview\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\responsive_preview\DeviceInterface;

/**
 * Defines the Device entity.
 *
 * @ConfigEntityType(
 *   id = "responsive_preview_device",
 *   label = @Translation("Responsive preview device"),
 *   handlers = {
 *     "list_builder" = "Drupal\responsive_preview\DeviceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\responsive_preview\Form\DeviceForm",
 *       "edit" = "Drupal\responsive_preview\Form\DeviceForm",
 *       "delete" = "Drupal\responsive_preview\Form\DeviceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\responsive_preview\DeviceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "device",
 *   admin_permission = "administer responsive preview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/config/user-interface/responsive-preview",
 *     "add-form" = "/admin/config/user-interface/responsive-preview/add",
 *     "edit-form" = "/admin/config/user-interface/responsive-preview/{responsive_preview_device}/edit",
 *     "delete-form" = "/admin/config/user-interface/responsive-preview/{responsive_preview_device}/delete"
 *   }
 * )
 */
class Device extends ConfigEntityBase implements DeviceInterface {

  /**
   * The Device ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Device label.
   *
   * @var string
   */
  protected $label;

  /**
   * Weight of this device.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * Default orientation.
   *
   * @var string
   *   Either 'landscape' or 'portrait'.
   */
  protected $orientation;

  /**
   * Dimension information.
   *
   * @var array
   *   Associative array with keys 'weight' (int), 'height' (int)
   *   and 'dppx' (int).
   */
  protected $dimensions;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = (int) $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrientation() {
    return $this->orientation;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrientation($orientation) {
    $this->orientation = $orientation;
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensions() {
    return $this->dimensions;
  }

  /**
   * {@inheritdoc}
   */
  public function setDimensions(array $dimensions) {
    $dimensions += [
      'width' => 0,
      'height' => 0,
      'dppx' => 0,
    ];
    $this->dimensions = $dimensions;
  }

}
