<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;

/**
 * Provides an edit form for webform handlers.
 */
class WebformHandlerEditForm extends WebformHandlerFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $form = parent::buildForm($form, $form_state, $webform, $webform_handler);
    $form['#title'] = $this->t('Edit @label handler', ['@label' => $this->webformHandler->label()]);

    // Delete action.
    $url = new Url('entity.webform.handler.delete_form', ['webform' => $webform->id(), 'webform_handler' => $this->webformHandler->getHandlerId()]);
    $this->buildDialogDeleteAction($form, $form_state, $url);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformHandler($webform_handler) {
    return $this->webform->getHandler($webform_handler);
  }

}
