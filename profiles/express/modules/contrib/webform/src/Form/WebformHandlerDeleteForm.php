<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Form for deleting a webform handler.
 */
class WebformHandlerDeleteForm extends ConfirmFormBase {

  use WebformDialogFormTrait;

  /**
   * The webform containing the webform handler to be deleted.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform handler to be deleted.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerInterface
   */
  protected $webformHandler;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @handler handler from the %webform webform?', ['%webform' => $this->webform->label(), '@handler' => $this->webformHandler->label()]);
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
  public function getCancelUrl() {
    return $this->webform->toUrl('handlers');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_handler_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $this->webform = $webform;
    $this->webformHandler = $this->webform->getHandler($webform_handler);

    $form = parent::buildForm($form, $form_state);
    $this->buildDialogConfirmForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->webform->deleteWebformHandler($this->webformHandler);
    drupal_set_message($this->t('The webform handler %name has been deleted.', ['%name' => $this->webformHandler->label()]));
    $form_state->setRedirectUrl($this->webform->toUrl('handlers'));
  }

}
