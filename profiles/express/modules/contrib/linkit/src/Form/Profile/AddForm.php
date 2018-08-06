<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Profile\AddForm.
 */

namespace Drupal\linkit\Form\Profile;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for profile addition forms.
 *
 * @see \Drupal\linkit\Profile\FormBase
 */
class AddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save and manage matchers');
    return $actions;
  }

}
