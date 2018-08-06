<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a webform to duplicate existing submissions.
 */
class WebformSubmissionDuplicateForm extends WebformSubmissionForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $this->setEntity($this->getEntity()->createDuplicate());
    parent::prepareEntity();
  }

  /**
   * Set webform state confirmation redirect and message.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setConfirmation(FormStateInterface $form_state) {
    parent::setConfirmation($form_state);

    // If the form is just reloading the duplicate form, redirect to the
    // new submission.
    $redirect = $form_state->getRedirect();
    $route_name = $this->getRouteMatch()->getRouteName();
    if ($redirect instanceof Url && $redirect->getRouteName() === $route_name) {
      /** @var \Drupal\webform\WebformSubmissionInterface $entity */
      $webform_submission = $this->entity;
      $source_entity = $webform_submission->getSourceEntity();

      $redirect_url= $this->requestHandler->getUrl($webform_submission, $source_entity, 'webform_submission.canonical');
      $form_state->setRedirectUrl($redirect_url);
    }
  }

}
