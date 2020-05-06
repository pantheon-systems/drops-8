<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a duplicate webform for a webform element.
 */
class WebformUiElementDuplicateForm extends WebformUiElementFormBase {

  /**
   * {@inheritdoc}
   */
  protected $operation = 'duplicate';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL, $parent_key = NULL, $type = NULL) {
    if (empty($key)) {
      throw new NotFoundHttpException();
    }

    $this->element = $webform->getElementDecoded($key);
    if ($this->element === NULL) {
      throw new NotFoundHttpException();
    }

    $element_initialized = $webform->getElement($key);

    $t_args = ['@title' => $element_initialized['#admin_title'] ?: $element_initialized['#title']];
    $form['#title'] = $this->t('Duplicate @title element', $t_args);

    $this->action = $this->t('created');
    return parent::buildForm($form, $form_state, $webform, NULL, $element_initialized['#webform_parent_key']);
  }

}
