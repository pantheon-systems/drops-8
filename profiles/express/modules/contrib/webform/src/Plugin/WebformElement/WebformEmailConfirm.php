<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'email_confirm' element.
 *
 * @WebformElement(
 *   id = "webform_email_confirm",
 *   label = @Translation("Email confirm"),
 *   description = @Translation("Provides a form element for double-input of email addresses."),
 *   category = @Translation("Advanced elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformEmailConfirm extends Email {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties() + [
      // Email confirm settings.
      'confirm__title' => '',
      'confirm__description' => '',
      'confirm__placeholder' => '',
      'flexbox' => '',
      // Wrapper.
      'wrapper_type' => 'fieldset',
    ];
    unset(
      $properties['multiple'],
      $properties['multiple__header_label'],
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    // Set confirm description.
    if (isset($element['#confirm__description'])) {
      $element['#confirm__description'] = WebformHtmlEditor::checkMarkup($element['#confirm__description']);
    }

    // If #flexbox is not set or an empty string, determine if the
    // webform is using a flexbox layout.
    if ((!isset($element['#flexbox']) || $element['#flexbox'] === '') && $webform_submission) {
      $webform = $webform_submission->getWebform();
      $element['#flexbox'] = $webform->hasFlexboxLayout();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['email_confirm'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email confirm settings'),
    ];
    $form['email_confirm']['confirm__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email confirm title'),
    ];
    $form['email_confirm']['confirm__description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Email confirm description'),
    ];
    $form['email_confirm']['confirm__placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email confirm placeholder'),
    ];
    $form['email_confirm']['flexbox'] = [
      '#type' => 'select',
      '#title' => $this->t('Use Flexbox'),
      '#description' => $this->t("If 'Automatic' is selected Flexbox layout will only be used if a 'Flexbox layout' element is included in the webform."),
      '#options' => [
        '' => $this->t('Automatic'),
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    $form['form']['display_container']['title_display']['#options'] = [
      'before' => $this->t('Before'),
      'after' => $this->t('After'),
      'inline' => $this->t('Inline'),
      'invisible' => $this->t('Invisible'),
      'none' => $this->t('None'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return [
      'mail_1' => $this->getAdminLabel($element) . '1 [' . $this->t('Email') . ']',
      'mail_2' => $this->getAdminLabel($element) . ' 2 [' . $this->t('Email') . ']',
    ];
  }

}
