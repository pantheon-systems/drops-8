<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a 'webform_wizard_page' element.
 *
 * @WebformElement(
 *   id = "webform_wizard_page",
 *   label = @Translation("Wizard page"),
 *   description = @Translation("Provides an element to display multiple form elements as a page in a multistep form wizard."),
 *   category = @Translation("Wizard"),
 * )
 */
class WebformWizardPage extends Details {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Page settings.
      'title' => '',
      'open' => FALSE,
      'prev_button_label' => '',
      'next_button_label' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['prev_button_label', 'next_button_label']);
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form['wizard_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page settings'),
    ];
    $form['wizard_page']['prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous page button label'),
      '#description' => $this->t('This is used for the Next Page button on the page before this page break.') . '<br/>' .
      $this->t('Defaults to: %value', ['%value' => $this->getDefaultSettings($webform, 'wizard_prev_button_label')]),
    ];
    $form['wizard_page']['next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next page button label'),
      '#description' => $this->t('This is used for the Previous Page button on the page after this page break.') . '<br/>' .
      $this->t('Defaults to: %value', ['%value' => $this->getDefaultSettings($webform, 'wizard_next_button_label')]),
    ];
    return $form;
  }

  /**
   * Get default from webform or global settings.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $name
   *   The name of the setting.
   *
   * @return string
   *   The setting's value.
   */
  protected function getDefaultSettings(WebformInterface $webform, $name) {
    return $webform->getSetting($name) ?: \Drupal::config('webform.settings')->get("settings.default_$name");
  }

}
