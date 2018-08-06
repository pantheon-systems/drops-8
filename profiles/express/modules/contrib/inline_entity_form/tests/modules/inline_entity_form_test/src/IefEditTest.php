<?php

namespace Drupal\inline_entity_form_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Tests Inline entity form element.
 */
class IefEditTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ief_edit_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL, $form_mode = 'default') {
    $form['inline_entity_form'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'node',
      '#bundle' => 'ief_test_custom',
      '#default_value' => $node,
      '#form_mode' => $form_mode,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
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
