<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'select' element.
 *
 * @WebformElement(
 *   id = "select",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Select.php/class/Select",
 *   label = @Translation("Select"),
 *   description = @Translation("Provides a form element for a drop-down menu or scrolling selection box."),
 *   category = @Translation("Options elements"),
 * )
 */
class Select extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Options settings.
      'multiple' => FALSE,
      'multiple_error' => '',
      'empty_option' => '',
      'empty_value' => '',
      'select2' => FALSE,
      'chosen' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    if (empty($element['#multiple'])) {
      if (!isset($element['#empty_option'])) {
        $element['#empty_option'] = empty($element['#required']) ? $this->t('- Select -') : $this->t('- None -');
      }
    }
    else {
      if (!isset($element['#empty_option'])) {
        $element['#empty_option'] = empty($element['#required']) ? $this->t('- None -') : NULL;
      }
      $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    }

    parent::prepare($element, $webform_submission);

    // Add select2 library and classes.
    if (!empty($element['#select2']) && $this->librariesManager->isIncluded('jquery.select2')) {
      $element['#attached']['library'][] = 'webform/webform.element.select2';
      $element['#attributes']['class'][] = 'js-webform-select2';
      $element['#attributes']['class'][] = 'webform-select2';
    }
    // Add chosen library and classes.
    elseif (!empty($element['#chosen']) && $this->librariesManager->isIncluded('jquery.chosen')) {
      $element['#attached']['library'][] = 'webform/webform.element.chosen';
      $element['#attributes']['class'][] = 'js-webform-chosen';
      $element['#attributes']['class'][] = 'webform-chosen';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['options']['select2'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select2'),
      '#description' => $this->t('Replace select element with jQuery <a href=":href">Select2</a> box.', [':href' => 'https://select2.github.io/']),
      '#return_value' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="properties[chosen]"]' => ['checked' => TRUE],
        ],
      ],
      '#access' => $this->librariesManager->isIncluded('jquery.select2'),
    ];
    $form['options']['chosen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Chosen'),
      '#description' => $this->t('Replace select element with jQuery <a href=":href">Chosen</a> box.', [':href' => 'https://harvesthq.github.io/chosen/']),
      '#return_value' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="properties[select2]"]' => ['checked' => TRUE],
        ],
      ],
      '#access' => $this->librariesManager->isIncluded('jquery.chosen'),
    ];
    if ($this->librariesManager->isIncluded('jquery.select2') && $this->librariesManager->isIncluded('jquery.chosen')) {
      $form['options']['select_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Select2 and Chosen provide very similar functionality, only one can enabled.'),
        '#access' => TRUE,
      ];
    }
    return $form;
  }

}
