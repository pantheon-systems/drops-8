<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Element\WebformTermsOfService as WebformTermsOfServiceElement;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'terms_of_service' element.
 *
 * @WebformElement(
 *   id = "webform_terms_of_service",
 *   default_key = "terms_of_service",
 *   label = @Translation("Terms of service"),
 *   description = @Translation("Provides a terms of service element."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformTermsOfService extends Checkbox {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'title' => $this->t('I agree to the {terms of service}.'),
      'terms_type' => 'modal',
      'terms_title' => '',
      'terms_content' => '',
    ] + parent::getDefaultProperties();
    unset(
      $properties['icheck'],
      $properties['field_prefix'],
      $properties['field_suffix'],
      $properties['description'],
      $properties['description_display'],
      $properties['title_display']

    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['terms_title', 'terms_content']);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Set default #title.
    if (empty($element['#title'])) {
      $element['#title'] = $this->getDefaultProperty('title');
    }

    // Backup #title and remove curly brackets.
    // Curly brackets are used to add link to #title when it is rendered.
    // @see \Drupal\webform\Element\WebformTermsOfService::preRenderCheckbox
    $element['#_webform_terms_of_service_title'] = $element['#title'];
    $element['#title'] = str_replace(['{', '}'], ['', ''], $element['#title']);

    parent::initialize($element);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Restore #title with curly brackets.
    if (isset($element['#_webform_terms_of_service_title'])) {
      $element['#title'] = $element['#_webform_terms_of_service_title'];
      unset($element['#_webform_terms_of_service_title']);
    }

    parent::prepare($element, $webform_submission);

    if (isset($element['#terms_content'])) {
      $element['#terms_content'] = WebformHtmlEditor::checkMarkup($element['#terms_content']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->t('I agree to the {terms of service}.'),
      '#required' => TRUE,
      '#terms_type' => WebformTermsOfServiceElement::TERMS_SLIDEOUT,
      '#terms_content' => '<em>' . $this->t('These are the terms of service.') . '</em>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['element']['title']['#description'] = $this->t('In order to create a link to your terms, wrap the words where you want your link to be in curly brackets.');

    $form['terms_of_service'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Terms of service settings'),
    ];
    $form['terms_of_service']['terms_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Terms display'),
      '#options' => [
        WebformTermsOfServiceElement::TERMS_MODAL => $this->t('Modal'),
        WebformTermsOfServiceElement::TERMS_SLIDEOUT => $this->t('Slideout'),
      ],
    ];
    $form['terms_of_service']['terms_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Terms title'),
    ];
    $form['terms_of_service']['terms_content'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Terms content'),
      '#required' => TRUE,
    ];
    return $form;
  }

}
