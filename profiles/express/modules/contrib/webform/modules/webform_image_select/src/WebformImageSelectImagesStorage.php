<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Serialization\Yaml;

/**
 * Storage controller class for "webform_image_select_images" configuration entities.
 */
class WebformImageSelectImagesStorage extends ConfigEntityStorage implements WebformImageSelectImagesStorageInterface {

  /**
   * Cached list of webforms that uses webform options.
   *
   * @var array
   */
  protected $usedByWebforms;

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $webform_images = $this->loadMultiple();
    $categories = [];
    foreach ($webform_images as $webform_image) {
      if ($category = $webform_image->get('category')) {
        $categories[$category] = $category;
      }
    }
    ksort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getImages() {
    $webform_images = $this->loadMultiple();
    @uasort($webform_images, [$this->entityType->getClass(), 'sort']);

    $uncategorized_images = [];
    $categorized_images = [];
    foreach ($webform_images as $id => $webform_image) {
      if ($category = $webform_image->get('category')) {
        $categorized_images[$category][$id] = $webform_image->label();
      }
      else {
        $uncategorized_images[$id] = $webform_image->label();
      }
    }
    return $uncategorized_images + $categorized_images;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedByWebforms(WebformImageSelectImagesInterface $webform_images) {
    if (!isset($this->usedByWebforms)) {
      // Looping through webform configuration instead of webform entities to
      // improve performance.
      $this->usedByWebforms = [];
      foreach ($this->configFactory->listAll('webform.webform.') as $webform_config_name) {
        $config = $this->configFactory->get($webform_config_name);
        $element_data = Yaml::encode($config->get('elements'));
        if (preg_match_all('/images\'\: ([a-z_]+)/', $element_data, $matches)) {
          $webform_id = $config->get('id');
          $webform_title = $config->get('title');
          foreach ($matches[1] as $options_id) {
            $this->usedByWebforms[$options_id][$webform_id] = $webform_title;
          }
        }
      }
    }

    $options_id = $webform_images->id();
    return (isset($this->usedByWebforms[$options_id])) ? $this->usedByWebforms[$options_id] : [];
  }

}
