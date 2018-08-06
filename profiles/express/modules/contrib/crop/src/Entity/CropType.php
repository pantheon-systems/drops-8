<?php

namespace Drupal\crop\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\crop\CropTypeInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Defines the Crop type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "crop_type",
 *   label = @Translation("Crop type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\crop\Form\CropTypeForm",
 *       "edit" = "Drupal\crop\Form\CropTypeForm",
 *       "delete" = "Drupal\crop\Form\CropTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\crop\CropTypeListBuilder",
 *   },
 *   admin_permission = "administer crop types",
 *   config_prefix = "type",
 *   bundle_of = "crop",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/crop/manage/{crop_type}",
 *     "delete-form" = "/admin/config/media/crop/manage/{crop_type}/delete",
 *   },
 *   constraints = {
 *     "CropTypeMachineNameValidation" = {},
 *     "CropTypeAspectRatioValidation" = {},
 *   }
 * )
 */
class CropType extends ConfigEntityBundleBase implements \IteratorAggregate, CropTypeInterface {

  /**
   * The machine name of this crop type.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the crop type.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this crop type.
   *
   * @var string
   */
  public $description;

  /**
   * The ratio of the image of this crop type.
   *
   * @var string
   */
  public $aspect_ratio;

  /**
   * Soft limit width in px.
   *
   * @var int
   */
  public $soft_limit_width;

  /**
   * Soft limit height in px.
   *
   * @var int
   */
  public $soft_limit_height;

  /**
   * Hard limit width in px.
   *
   * @var int
   */
  public $hard_limit_width;

  /**
   * Hard limit height in px.
   *
   * @var int
   */
  public $hard_limit_height;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAspectRatio() {
    return $this->aspect_ratio;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $violations = $this->getTypedData()->validate();
    return new ConstraintViolationList(iterator_to_array($violations));
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator();
  }

  /**
   * {@inheritdoc}
   */
  public static function getCropTypeNames() {
    return array_map(
      function ($bundle_info) {
        return $bundle_info['label'];
      },
      \Drupal::service('entity_type.bundle.info')->getBundleInfo('crop')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSoftLimit() {
    return [
      'width' => $this->soft_limit_width,
      'height' => $this->soft_limit_height,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getHardLimit() {
    return [
      'width' => $this->hard_limit_width,
      'height' => $this->hard_limit_height,
    ];
  }

}
