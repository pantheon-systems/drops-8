<?php

namespace Drupal\webform\Element;

/**
 * Trait for term reference elements.
 */
trait WebformTermReferenceTrait {

  /**
   * Set referencable term entities as options for an element.
   *
   * @param array $element
   *   An element.
   */
  public static function setOptions(array &$element) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if (!empty($element['#options'])) {
      return;
    }

    if (!\Drupal::moduleHandler()->moduleExists('taxonomy') || empty($element['#vocabulary'])) {
      $element['#options'] = [];
      return;
    }

    if (!empty($element['#breadcrumb'])) {
      $element['#options'] = static::getOptionsBreadcrumb($element, $language);
    }
    else {
      $element['#options'] = static::getOptionsTree($element, $language);
    }
  }

  /**
   * Get options to term breadcrumb.
   *
   * @param array $element
   *   The term reference element.
   * @param string $language
   *   The language to be displayed.
   *
   * @return array
   *   An associative array of term options formatted as a breadcrumbs.
   */
  protected static function getOptionsBreadcrumb(array $element, $language) {
    $element += ['#breadcrumb_delimiter' => ' › '];

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    $tree = $taxonomy_storage->loadTree($element['#vocabulary'], 0, NULL, TRUE);

    $options = [];
    $breadcrumb = [];
    foreach ($tree as $item) {
      // Set the item in the correct language for display.
      $item = $entity_repository->getTranslationFromContext($item);
      $breadcrumb[$item->depth] = $item->getName();
      $breadcrumb = array_slice($breadcrumb, 0, $item->depth + 1);
      $options[$item->id()] = implode($element['#breadcrumb_delimiter'], $breadcrumb);
    }
    return $options;
  }

  /**
   * Get options to term tree.
   *
   * @param array $element
   *   The term reference element.
   * @param string $language
   *   The language to be displayed.
   *
   * @return array
   *   An associative array of term options formatted as a tree.
   */
  protected static function getOptionsTree(array $element, $language) {
    $element += ['#tree_delimiter' => '-'];

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    $tree = $taxonomy_storage->loadTree($element['#vocabulary'], 0, NULL, TRUE);

    $options = [];
    foreach ($tree as $item) {
      // Set the item in the correct language for display.
      $item = $entity_repository->getTranslationFromContext($item);
      $options[$item->id()] = str_repeat($element['#tree_delimiter'], $item->depth) . $item->getName();
    }
    return $options;
  }

}
