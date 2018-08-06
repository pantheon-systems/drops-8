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
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL) {
    if (empty($key)) {
      throw new NotFoundHttpException();
    }

    $this->element = $webform->getElementDecoded($key);
    if ($this->element === NULL) {
      throw new NotFoundHttpException();
    }

    $element_initialized = $webform->getElement($key);

    $form['#title'] = $this->t('Duplicate @title element', [
      '@title' => (!empty($this->element['#title'])) ? $this->element['#title'] : $key,
    ]);

    $this->action = $this->t('created');
    return parent::buildForm($form, $form_state, $webform, NULL, $element_initialized['#webform_parent_key']);
  }

}
