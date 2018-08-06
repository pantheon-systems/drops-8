<?php

namespace Drupal\inline_entity_form\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Node inline form handler.
 */
class NodeInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => $this->t('node'),
      'plural' => $this->t('nodes'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    $fields['status'] = [
      'type' => 'field',
      'label' => $this->t('Status'),
      'weight' => 100,
      'display_options' => [
        'settings' => [
          'format' => 'custom',
          'format_custom_false' => $this->t('Unpublished'),
          'format_custom_true' => $this->t('Published'),
        ],
      ],
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    // Remove the "Revision log" textarea,  it can't be disabled in the
    // form display and doesn't make sense in the inline form context.
    $entity_form['revision_log']['#access'] = FALSE;

    return $entity_form;
  }

}
