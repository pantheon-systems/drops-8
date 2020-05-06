<?php

namespace Drupal\webform_options_custom;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Serialization\Yaml;

/**
 * Storage controller class for "webform_options_custom" configuration entities.
 */
class WebformOptionsCustomStorage extends ConfigEntityStorage implements WebformOptionsCustomStorageInterface {

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
    $webform_options_custom = $this->loadMultiple();
    $categories = [];
    foreach ($webform_options_custom as $webform_image) {
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
  public function getOptionsCustom() {
    $webform_options_custom = $this->loadMultiple();
    @uasort($webform_options_custom, [$this->entityType->getClass(), 'sort']);

    $uncategorized_options_custom = [];
    $categorized_options_custom = [];
    foreach ($webform_options_custom as $id => $webform_image) {
      if ($category = $webform_image->get('category')) {
        $categorized_options_custom[$category][$id] = $webform_image->label();
      }
      else {
        $uncategorized_options_custom[$id] = $webform_image->label();
      }
    }
    return $uncategorized_options_custom + $categorized_options_custom;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedByWebforms(WebformOptionsCustomInterface $webform_options_custom) {
    if (!isset($this->usedByWebforms)) {
      // Looping through webform configuration instead of webform entities to
      // improve performance.
      $this->usedByWebforms = [];
      foreach ($this->configFactory->listAll('webform.webform.') as $webform_config_name) {
        $config = $this->configFactory->get($webform_config_name);
        $element_data = Yaml::encode($config->get('elements'));
        if (preg_match_all('/webform_options_custom(?:_entity)?\:([a-z_]+)\'/', $element_data, $matches)) {
          $webform_id = $config->get('id');
          $webform_title = $config->get('title');
          foreach ($matches[1] as $options_id) {
            $this->usedByWebforms[$options_id][$webform_id] = $webform_title;
          }
        }
      }
    }

    $options_id = $webform_options_custom->id();
    return (isset($this->usedByWebforms[$options_id])) ? $this->usedByWebforms[$options_id] : [];
  }

}
