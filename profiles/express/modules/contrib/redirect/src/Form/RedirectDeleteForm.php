<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class RedirectDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the URL redirect from %source to %redirect?', array('%source' => $this->entity->getSourceUrl(), '%redirect' => $this->entity->getRedirectUrl()->toString()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('redirect.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message(t('The redirect %redirect has been deleted.', array('%redirect' => $this->entity->getRedirectUrl()->toString())));
    $form_state->setRedirect('redirect.list');
  }

}
