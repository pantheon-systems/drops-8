<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformArrayHelper;
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
  protected function defineDefaultProperties() {
    $properties = [
      // Options settings.
      'multiple' => FALSE,
      'multiple_error' => '',
      'empty_option' => '',
      'empty_value' => '',
      'select2' => FALSE,
      'choices' => FALSE,
      'chosen' => FALSE,
      'placeholder' => '',
      'help_display' => '',
      'size' => '',
    ] + parent::defineDefaultProperties();
    return $properties;
  }

  /****************************************************************************/

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

    // If select2, choices, or chosen is not available,
    // see if we can use the alternative.
    $select2_exists = $this->librariesManager->isIncluded('jquery.select2');
    $choices_exists = $this->librariesManager->isIncluded('choices');
    $chosen_exists = $this->librariesManager->isIncluded('jquery.chosen');
    $default_select = ($select2_exists ? '#select2' :
      ($choices_exists ? '#choices' :
        ($chosen_exists ? '#chosen' : NULL)
      )
    );
    if (isset($element['#select2']) && !$select2_exists) {
      $element['#' . $default_select] = TRUE;
    }
    elseif (isset($element['#choices']) && !$choices_exists) {
      $element['#' . $default_select] = TRUE;
    }
    elseif (isset($element['#chosen']) && !$chosen_exists) {
      $element['#' . $default_select] = TRUE;
    }

    // Enhance select element using select2, chosen, or choices.
    if (isset($element['#select2']) && $select2_exists) {
      $element['#attached']['library'][] = 'webform/webform.element.select2';
      $element['#attributes']['class'][] = 'js-webform-select2';
      $element['#attributes']['class'][] = 'webform-select2';
    }
    elseif (isset($element['#choices']) && $choices_exists) {
      $element['#attached']['library'][] = 'webform/webform.element.choices';
      $element['#attributes']['class'][] = 'js-webform-choices';
      $element['#attributes']['class'][] = 'webform-choices';
    }
    elseif (isset($element['#chosen']) && $chosen_exists) {
      $element['#attached']['library'][] = 'webform/webform.element.chosen';
      $element['#attributes']['class'][] = 'js-webform-chosen';
      $element['#attributes']['class'][] = 'webform-chosen';
    }

    // Set placeholder as data attributes for select2, choices or chosen.
    if (!empty($element['#placeholder'])) {
      $element['#attributes']['data-placeholder'] = $element['#placeholder'];
    }
    // Set limit as data attributes for select2, choices or chosen.
    if (isset($element['#multiple']) && $element['#multiple'] > 1) {
      $element['#attributes']['data-limit'] = $element['#multiple'];
    }

    // Attach library which allows options to be disabled via JavaScript.
    $element['#attached']['library'][] = 'webform/webform.element.select';

    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Select2, Chosen, and/or Choices enhancements.
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::form
    $select2_exists = $this->librariesManager->isIncluded('jquery.select2');
    $choices_exists = $this->librariesManager->isIncluded('choices');
    $chosen_exists = $this->librariesManager->isIncluded('jquery.chosen');

    $form['options']['select2'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select2'),
      '#description' => $this->t('Replace select element with jQuery <a href=":href">Select2</a> select box.', [':href' => 'https://select2.github.io/']),
      '#return_value' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="properties[chosen]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!$select2_exists) {
      $form['options']['select2']['#access'] = FALSE;
    }
    $form['options']['choices'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Choices'),
      '#description' => $this->t('Replace select element with <a href=":href">Choice.js</a> select box.', [':href' => 'https://joshuajohnson.co.uk/Choices/']),
      '#return_value' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="properties[select2]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!$choices_exists) {
      $form['options']['choices']['#access'] = FALSE;
    }
    $form['options']['chosen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Chosen'),
      '#description' => $this->t('Replace select element with jQuery <a href=":href">Chosen</a> select box.', [':href' => 'https://harvesthq.github.io/chosen/']),
      '#return_value' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="properties[select2]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!$chosen_exists) {
      $form['options']['chosen']['#access'] = FALSE;
    }
    if (($select2_exists + $chosen_exists + $choices_exists) > 1) {
      $select_libraries = [];
      if ($select2_exists) {
        $select_libraries[] = $this->t('Select2');
      }
      if ($choices_exists) {
        $select_libraries[] = $this->t('Choices');
      }
      if ($chosen_exists) {
        $select_libraries[] = $this->t('Chosen');
      }
      $t_args = [
        '@libraries' => WebformArrayHelper::toString($select_libraries),
      ];
      $form['options']['select_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('@libraries provide very similar functionality, only one should be enabled.', $t_args),
        '#access' => TRUE,
      ];
    }

    // Add states to placeholder if custom library is supported and the
    // select menu supports multiple values.
    $placeholder_states = [];
    if ($select2_exists) {
      $placeholder_states[] = [':input[name="properties[select2]"]' => ['checked' => TRUE]];
    }
    if ($chosen_exists) {
      if (isset($form['form']['placeholder']['#states']['visible'])) {
        $placeholder_states[] = 'or';
      }
      $placeholder_states[] = [':input[name="properties[chosen]"]' => ['checked' => TRUE]];
    }
    if ($choices_exists) {
      if (isset($form['form']['placeholder']['#states']['visible'])) {
        $placeholder_states[] = 'or';
      }
      $placeholder_states[] = [':input[name="properties[choices]"]' => ['checked' => TRUE]];
    }
    if ($placeholder_states) {
      $form['form']['placeholder']['#states']['visible'] = [
        [
        ':input[name="properties[multiple][container][cardinality]"]' => ['value' => 'number'],
        ':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1],
        ],
        $placeholder_states,
      ];
    }
    else {
      $form['form']['placeholder']['#access'] = FALSE;
    }

    // Update multiple select size property.
    $form['form']['size_container']['size']['#description'] = $this->t('Specifies the number of visible options.');
    $form['form']['size_container']['#states'] = [
      'visible' => [
        ':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1],
      ],
    ];

    return $form;
  }

}
