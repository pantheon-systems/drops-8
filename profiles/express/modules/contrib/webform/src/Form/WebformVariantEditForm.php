<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;

/**
 * Provides an edit form for webform variants.
 */
class WebformVariantEditForm extends WebformVariantFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_variant = NULL) {
    $form = parent::buildForm($form, $form_state, $webform, $webform_variant);
    $form['#title'] = $this->t('Edit @label variant', ['@label' => $this->webformVariant->label()]);

    // Delete action.
    $url = new Url('entity.webform.variant.delete_form', ['webform' => $webform->id(), 'webform_variant' => $this->webformVariant->getVariantId()]);
    $this->buildDialogDeleteAction($form, $form_state, $url);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformVariant($webform_variant) {
    return $this->webform->getVariant($webform_variant);
  }

}
