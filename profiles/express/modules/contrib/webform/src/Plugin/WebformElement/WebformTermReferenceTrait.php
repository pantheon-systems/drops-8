<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides an 'term_reference' trait.
 */
trait WebformTermReferenceTrait {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    if ($vocabularies = Vocabulary::loadMultiple()) {
      $vocabulary = reset($vocabularies);
      $vocabulary_id = $vocabulary->id();
    }
    else {
      $vocabulary_id = 'tags';
    }

    // Make sure the vocabulary does not have more than 250 terms.
    // This will prevent a fatal memory error when
    // previewing term related elements.
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tree = $taxonomy_storage->loadTree($vocabulary_id);
    if (count($tree) > 250) {
      $vocabulary_id = NULL;
    }

    return parent::preview() + [
      '#vocabulary' => $vocabulary_id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['term_reference'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Term reference settings'),
      '#weight' => -40,
    ];
    $form['term_reference']['vocabulary'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Vocabulary'),
      '#target_type' => 'taxonomy_vocabulary',
      '#selection_handler' => 'default:taxonomy_vocabulary',
    ];
    $form['term_reference']['breadcrumb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display term hierarchy using breadcrumbs'),
      '#return_value' => TRUE,
    ];
    $form['term_reference']['breadcrumb_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Breadcrumb delimiter'),
      '#size' => 10,
      '#states' => [
        'visible' => [
          [':input[name="properties[breadcrumb]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="properties[format]"]' => ['value' => 'breadcrumb']],
        ],
        'required' => [
          [':input[name="properties[breadcrumb]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="properties[format]"]' => ['value' => 'breadcrumb']],
        ],
      ],
    ];
    $form['term_reference']['tree_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tree delimiter'),
      '#size' => 10,
      '#states' => [
        'visible' => [
          ':input[name="properties[breadcrumb]"]' => [
            'checked' => FALSE,
          ],
        ],
        'required' => [
          ':input[name="properties[breadcrumb]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['term_reference']['scroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow terms to be scrollable'),
      '#return_value' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Overrides: \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::validateConfigurationForm.
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(array $element) {
    return 'taxonomy_term';
  }

}
