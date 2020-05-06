<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a generic base class for a webform deletion form.
 */
abstract class WebformDeleteFormBase extends ConfirmFormBase implements WebformDeleteFormInterface {

  use WebformDialogFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'webform_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'confirmation';
    $form['#theme'] = 'confirm_form';
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // Title.
    $form['#title'] = $this->getQuestion();

    // Warning.
    $form['warning'] = $this->getWarning();

    // Description.
    $form['description'] = $this->getDescription();

    // Details and confirm input.
    $details = $this->getDetails();
    $confirm_input = $this->getConfirmInput();
    if ($details) {
      $form['details'] = $details;
    }
    if (!$details && $confirm_input) {
      $form['hr'] = ['#markup' => '<p><hr/></p>'];
    }
    if ($confirm_input) {
      $form['confirm'] = $confirm_input;
    }

    // Actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete this?') . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

}
