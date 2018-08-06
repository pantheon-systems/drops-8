<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a webform element for term checkboxes.
 *
 * @FormElement("webform_term_checkboxes")
 */
class WebformTermCheckboxes extends Checkboxes {

  use WebformTermReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#vocabulary' => '',
      '#tree_delimiter' => '&nbsp;&nbsp;&nbsp;',
      '#breadcrumb' => FALSE,
      '#breadcrumb_delimiter' => ' › ',
      '#scroll' => TRUE,
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    static::setOptions($element);
    $element = parent::processCheckboxes($element, $form_state, $complete_form);

    if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      return $element;
    }

    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tree = $taxonomy_storage->loadTree($element['#vocabulary'], 0, NULL, TRUE);

    if (empty($element['#breadcrumb'])) {
      foreach ($tree as $item) {
        $element[$item->id()]['#title'] = $item->label();
        $element[$item->id()]['#field_prefix'] = str_repeat($element['#tree_delimiter'], $item->depth);
      }
    }

    $element['#attributes']['class'][] = 'js-webform-term-checkboxes';
    $element['#attributes']['class'][] = 'webform-term-checkboxes';
    if (!empty($element['#scroll'])) {
      $element['#attributes']['class'][] = 'webform-term-checkboxes-scroll';
    }
    $element['#attached']['library'][] = 'webform/webform.element.term_checkboxes';

    return $element;
  }

  /**
   * {@inheritdoc}
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
      $options[$item->id()] = $item->getName();
    }
    return $options;
  }

}
