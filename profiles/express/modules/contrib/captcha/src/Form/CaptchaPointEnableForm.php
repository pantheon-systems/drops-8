<?php

namespace Drupal\captcha\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a Captcha Point.
 */
class CaptchaPointEnableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to enable the Captcha?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will enable the captcha.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('captcha_point.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Enable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->enable();
    $this->entity->save();
    drupal_set_message($this->t('Captcha point %label has been enabled.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('captcha_point.list');
  }

}
