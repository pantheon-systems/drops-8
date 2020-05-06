<?php

namespace Drupal\webform_image_select\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform_image_select\WebformImageSelectImagesInterface;

/**
 * Defines the webform image select images entity.
 *
 * @ConfigEntityType(
 *   id = "webform_image_select_images",
 *   label = @Translation("Webform images"),
 *   label_collection = @Translation("Images"),
 *   label_singular = @Translation("images"),
 *   label_plural = @Translation("images"),
 *   label_count = @PluralTranslation(
 *     singular = "@count images",
 *     plural = "@count images",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\webform_image_select\WebformImageSelectImagesStorage",
 *     "access" = "Drupal\webform_image_select\WebformImageSelectImagesAccessControlHandler",
 *     "list_builder" = "Drupal\webform_image_select\WebformImageSelectImagesListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_image_select\WebformImageSelectImagesForm",
 *       "edit" = "Drupal\webform_image_select\WebformImageSelectImagesForm",
 *       "source" = "Drupal\webform_image_select\WebformImageSelectImagesForm",
 *       "duplicate" = "Drupal\webform_image_select\WebformImageSelectImagesForm",
 *       "delete" = "Drupal\webform_image_select\WebformImageSelectImagesDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/webform/config/images/manage/add",
 *     "edit-form" = "/admin/structure/webform/config/images/manage/{webform_image_select_images}/edit",
 *     "source-form" = "/admin/structure/webform/config/images/manage/{webform_image_select_images}/source",
 *     "duplicate-form" = "/admin/structure/webform/config/images/manage/{webform_image_select_images}/duplicate",
 *     "delete-form" = "/admin/structure/webform/config/images/manage/{webform_image_select_images}/delete",
 *     "collection" = "/admin/structure/webform/config/images/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "category",
 *     "images",
 *   }
 * )
 */
class WebformImageSelectImages extends ConfigEntityBase implements WebformImageSelectImagesInterface {

  use StringTranslationTrait;

  /**
   * The images ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The images UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The images label.
   *
   * @var string
   */
  protected $label;

  /**
   * The images category.
   *
   * @var string
   */
  protected $category;

  /**
   * The images.
   *
   * @var string
   */
  protected $images;

  /**
   * The images decoded.
   *
   * @var string
   */
  protected $imagesDecoded;

  /**
   * {@inheritdoc}
   */
  public function getImages() {
    if (!isset($this->imagesDecoded)) {
      try {
        $options = Yaml::decode($this->images);
        // Since YAML supports simple values.
        $options = (is_array($options)) ? $options : [];
      }
      catch (\Exception $exception) {
        $link = $this->toLink($this->t('Edit'), 'edit-form')->toString();
        \Drupal::logger('webform_image_select')->notice('%title images are not valid. @message', ['%title' => $this->label(), '@message' => $exception->getMessage(), 'link' => $link]);
        $options = FALSE;
      }
      $this->imagesDecoded = $options;
    }
    return $this->imagesDecoded;
  }

  /**
   * {@inheritdoc}
   */
  public function setImages(array $images) {
    $this->images = Yaml::encode($images);
    $this->imagesDecoded = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear cached properties.
    $this->imagesDecoded = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_label = $a->get('category') . $a->label();
    $b_label = $b->get('category') . $b->label();
    return strnatcasecmp($a_label, $b_label);
  }

  /**
   * {@inheritdoc}
   */
  public static function getElementImages(array &$element) {
    // If element already has #images return them.
    if (isset($element['#images']) && is_array($element['#images'])) {
      return $element['#images'];
    }

    // Return empty image if element does not define an images id.
    if (empty($element['#images']) || !is_string($element['#images'])) {
      return [];
    }

    // If images have been set return them.
    // This allows dynamic images to be overridden.
    $id = $element['#images'];
    if ($webform_images = WebformImageSelectImages::load($id)) {
      $images = $webform_images->getImages() ?: [];
    }
    else {
      $images = [];
    }

    // Alter images using hook_webform_image_select_images_alter()
    // and/or hook_webform_image_select_images_WEBFORM_IMAGE_SELECT_IMAGES_ID_alter() hook.
    // @see webform.api.php
    \Drupal::moduleHandler()->alter('webform_image_select_images_' . $id, $images, $element);
    \Drupal::moduleHandler()->alter('webform_image_select_images', $images, $element, $id);

    // Log empty images.
    if (empty($images)) {
      \Drupal::logger('webform_image_select')->notice('Images %id do not exist.', ['%id' => $id]);
    }

    return $images;
  }

}
