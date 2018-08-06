<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "webform_options" configuration entities.
 */
class WebformOptionsStorage extends ConfigEntityStorage implements WebformOptionsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $webform_options = $this->loadMultiple();
    $categories = [];
    foreach ($webform_options as $webform_option) {
      if ($category = $webform_option->get('category')) {
        $categories[$category] = $category;
      }
    }
    ksort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $webform_options = $this->loadMultiple();
    @uasort($webform_options, [$this->entityType->getClass(), 'sort']);

    $uncategorized_options = [];
    $categorized_options = [];
    foreach ($webform_options as $id => $webform_option) {
      if ($category = $webform_option->get('category')) {
        $categorized_options[$category][$id] = $webform_option->label();
      }
      else {
        $uncategorized_options[$id] = $webform_option->label();
      }
    }
    return $uncategorized_options + $categorized_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getLikerts() {
    $webform_options = $this->loadMultiple();
    @uasort($webform_options, [$this->entityType->getClass(), 'sort']);

    $likert_options = [];
    foreach ($webform_options as $id => $webform_option) {
      if (strpos($id, 'likert_') === 0) {
        $likert_options[$id] = str_replace(t('Likert') . ': ', '', $webform_option->label());
      }
    }
    return $likert_options;
  }

}
