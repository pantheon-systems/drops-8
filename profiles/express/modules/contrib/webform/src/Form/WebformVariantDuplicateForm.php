<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a duplicate form for webform variant.
 */
class WebformVariantDuplicateForm extends WebformVariantAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_variant = NULL) {
    $form = parent::buildForm($form, $form_state, $webform, $webform_variant);
    $form['#title'] = $this->t('Duplicate @label variant', ['@label' => $this->webformVariant->label()]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformVariant($webform_variant) {
    $webform_variant = clone $this->webform->getVariant($webform_variant);
    $webform_variant->setVariantId(NULL);
    // Initialize the variant an pass in the webform.
    $webform_variant->setWebform($this->webform);
    // Set the initial weight so this variant comes last.
    $webform_variant->setWeight(count($this->webform->getVariants()));
    return $webform_variant;
  }

}
