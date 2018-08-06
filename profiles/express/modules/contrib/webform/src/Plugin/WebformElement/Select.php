<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
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
    return [
      // Options settings.
      'multiple' => FALSE,
      'multiple_error' => '',
      'empty_option' => '',
      'empty_value' => '',
      'select2' => FALSE,
      'chosen' => FALSE,
    ] + parent::getDefaultProperties();
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
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $config = $this->configFactory->get('webform.settings');

    // Always include empty option.
    // Note: #multiple select menu does support empty options.
    // @see \Drupal\Core\Render\Element\Select::processSelect
    if (!isset($element['#empty_option']) && empty($element['#multiple'])) {
      $required = isset($element['#states']['required']) ? TRUE : !empty($element['#required']);
      $empty_option = $required
        ? ($config->get('element.default_empty_option_required') ?: $this->t('- Select -'))
        : ($config->get('element.default_empty_option_optional') ?: $this->t('- None -'));
      if ($config->get('element.default_empty_option')) {
        $element['#empty_option'] = $empty_option;
      }
      // Copied from: \Drupal\Core\Render\Element\Select::processSelect.
      elseif (($required && !isset($element['#default_value'])) || isset($element['#empty_value'])) {
        $element['#empty_option'] = $empty_option;
      }
    }

    if (!empty($element['#multiple'])) {
      $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    }

    parent::prepare($element, $webform_submission);

    WebformElementHelper::enhanceSelect($element);
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
    ];
    if ($this->librariesManager->isExcluded('jquery.select2')) {
      $form['options']['select2']['#access'] = FALSE;
    }
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

    ];
    if ($this->librariesManager->isExcluded('jquery.chosen')) {
      $form['options']['chosen']['#access'] = FALSE;
    }
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
