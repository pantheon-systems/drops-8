<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a delete webform.
 */
class WebformEntityDeleteForm extends EntityDeleteForm {

  use WebformDialogTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to the delete this webform.'),
      '#required' => TRUE,
      '#weight' => 10,
    ];

    return $this->buildConfirmFormDialog($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return Url::fromRoute('entity.webform.collection');
  }

}
