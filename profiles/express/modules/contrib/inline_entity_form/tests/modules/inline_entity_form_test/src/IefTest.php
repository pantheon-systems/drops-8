<?php

namespace Drupal\inline_entity_form_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tests Inline entity form element.
 */
class IefTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ief_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $form_mode = 'default') {
    $form['inline_entity_form'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'node',
      '#bundle' => 'ief_test_custom',
      '#form_mode' => $form_mode,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form['inline_entity_form']['#entity'];
    drupal_set_message(t('Created @entity_type @label.', ['@entity_type' => $entity->getEntityType()->getLabel(), '@label' => $entity->label()]));
  }

}
