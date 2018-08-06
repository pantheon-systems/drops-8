<?php

namespace Drupal\metatag\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to revert Metatag defaults entities.
 */
class MetatagDefaultsRevertForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert %name to its default values?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.metatag_defaults.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->revert();

    drupal_set_message(
      $this->t('Reverted @label defaults.',
        [
          '@label' => $this->entity->label()
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
