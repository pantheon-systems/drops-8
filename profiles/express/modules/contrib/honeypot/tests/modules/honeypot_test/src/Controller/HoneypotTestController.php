<?php

namespace Drupal\honeypot_test\Controller;

use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for honeypot_test routes.
 */
class HoneypotTestController {

  /**
   * Page that triggers a programmatic form submission.
   *
   * Returns the validation errors triggered by the form submission as json.
   */
  public function submitFormPage() {
    $form_state = new FormState();
    $values = [
      'name' => 'robo-user',
      'mail' => 'robouser@example.com',
      'op' => t('Submit'),
    ];
    $form_state->setValues($values);
    \Drupal::formBuilder()->submitForm('\Drupal\user\Form\UserPasswordForm', $form_state);

    return new JsonResponse($form_state->getErrors());
  }

}
