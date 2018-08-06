<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a duplicate form for webform handler.
 */
class WebformHandlerDuplicateForm extends WebformHandlerAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $form = parent::buildForm($form, $form_state, $webform, $webform_handler);
    $form['#title'] = $this->t('Duplicate @label handler', ['@label' => $this->webformHandler->label()]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformHandler($webform_handler) {
    $webform_handler = clone $this->webform->getHandler($webform_handler);
    $webform_handler->setHandlerId(NULL);
    // Initialize the handler an pass in the webform.
    $webform_handler->setWebform($this->webform);
    // Set the initial weight so this handler comes last.
    $webform_handler->setWeight(count($this->webform->getHandlers()));
    return $webform_handler;
  }

}
