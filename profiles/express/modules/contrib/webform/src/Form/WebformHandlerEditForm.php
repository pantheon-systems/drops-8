<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformHandler($webform_handler) {
    return $this->webform->getHandler($webform_handler);
  }

}
