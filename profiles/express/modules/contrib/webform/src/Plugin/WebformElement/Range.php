<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'range' element.
 *
 * @WebformElement(
 *   id = "range",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Range.php/class/Range",
 *   label = @Translation("Range"),
 *   description = @Translation("Provides a form element for input of a number within a specific range using a slider."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Range extends NumericBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Number settings.
      'min' => 0,
      'max' => 100,
      'step' => 1,
      // Output settings.
      'output' => '',
      'output__field_prefix' => '',
      'output__field_suffix' => '',
      'output__attributes' => [],
    ] + parent::defineDefaultProperties();
    unset(
      $properties['size'],
      $properties['minlength'],
      $properties['maxlength'],
      $properties['placeholder'],
      $properties['autocomplete'],
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

    // Set default min/max. Default step is defined via parent::prepare().
    $element += [
      '#min' => $this->getDefaultProperty('min'),
      '#max' => $this->getDefaultProperty('max'),
    ];

    // If no custom range output is defined then exit.
    if (empty($element['#output'])) {
      return;
    }

    $webform_key = (isset($element['#webform_key'])) ? $element['#webform_key'] : 'range';

    if (in_array($element['#output'], ['above', 'below'])) {
      $element += ['#output__attributes' => []];
      $attributes = new Attribute($element['#output__attributes']);
      $attributes['for'] = $webform_key;
      $attributes['data-display'] = $element['#output'];
      if (isset($element['#output__field_prefix'])) {
        $attributes['data-field-prefix'] = $element['#output__field_prefix'];
      }
      if (isset($element['#output__field_suffix'])) {
        $attributes['data-field-suffix'] = $element['#output__field_suffix'];
      }
      $element['#children'] = Markup::create('<output' . $attributes . '></output>');
    }
    else {
      // Create output (number) element.
      $output = [
        '#type' => 'number',
        '#title' => $element['#title'],
        '#title_display' => 'invisible',
        '#id' => $webform_key . '__output',
        '#name' => $webform_key . '__output',
      ];

      // Copy range (number) properties to output element.
      $properties = ['#min', '#max', '#step', '#disabled'];
      $output += array_intersect_key($element, array_combine($properties, $properties));

      // Copy custom output properties to output element.
      foreach ($element as $key => $value) {
        if (strpos($key, '#output__') === 0) {
          $output_key = str_replace('#output__', '#', $key);
          $output[$output_key] = $value;
        }
      }

      // Manually copy disabled from input to output because the output is not
      // handled by the FormBuilder.
      // @see \Drupal\Core\Form\FormBuilder::handleInputElement
      if (!empty($output['#disabled'])) {
        $output['#attributes']['disabled'] = TRUE;
      }

      // Set the output's input name to an empty string so that it is not
      // posted back to the server.
      $output['#attributes']['name'] = '';

      // Calculate the output's width based on the #max number's string length.
      $output['#attributes'] += ['style' => ''];
      $output['#attributes']['style'] .= ($output['#attributes']['style'] ? ';' : '') . 'width:' . (strlen($element['#max'] . '') + 1) . 'em';

      // Append output element as a child.
      if ($element['#output'] === 'left') {
        if (isset($element['#field_prefix'])) {
          $element['#field_prefix'] = [
            'output' => $output,
            'delimiter' => ['#markup' => '<span class="webform-range-output-delimiter"></span>'],
            'content' => (is_array($element['#field_prefix'])) ? $element['#field_prefix'] : ['#markup' => $element['#field_prefix']],
          ];
        }
        else {
          $element['#field_suffix'] = [
            'output' => $output,
            'delimiter' => ['#markup' => '<span class="webform-range-output-delimiter"></span>'],
          ];
        }
      }
      else {
        if (isset($element['#field_suffix'])) {
          $element['#field_suffix'] = [
            'content' => (is_array($element['#field_suffix'])) ? $element['#field_suffix'] : ['#markup' => $element['#field_suffix']],
            'delimiter' => ['#markup' => '<span class="webform-range-output-delimiter"></span>'],
            'output' => $output,
          ];
        }
        else {
          $element['#field_suffix'] = [
            'delimiter' => ['#markup' => '<span class="webform-range-output-delimiter"></span>'],
            'output' => $output,
          ];
        }
      }
    }

    $element['#attached']['library'][] = 'webform/webform.element.range';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateRange'];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#output' => 'below',
      '#output__field_prefix' => '$',
      '#output__field_suffix' => '.00',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['number']['#title'] = $this->t('Range settings');

    $form['output'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Range output settings'),
    ];
    $form['output']['output'] = [
      '#type' => 'select',
      '#title' => $this->t("Output the range's value"),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        'right' => $this->t('Right'),
        'left' => $this->t('Left'),
        'above' => $this->t('Above (Floating)'),
        'below' => $this->t('Below (Floating)'),
      ],
    ];

    $form['output']['output_container'] = $this->getFormInlineContainer() + [
      '#states' => [
        'visible' => [':input[name="properties[output]"]' => ['!value' => '']],
      ],
    ];

    $form['output']['output_container']['output__field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output prefix'),
      '#description' => $this->t('Text or code that is placed directly in front of the output. This can be used to prefix an output with a constant string. Examples=> $, #, -.'),
      '#size' => 10,
    ];
    $form['output']['output_container']['output__field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output suffix'),
      '#description' => $this->t('Text or code that is placed directly after the output. This can be used to add a unit to an output. Examples=> lb, kg, %.'),
      '#size' => 10,
    ];
    $form['output']['output__attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Output'),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.classes'),
      '#states' => [
        'visible' => [':input[name="properties[output]"]' => ['!value' => '']],
      ],
    ];

    return $form;
  }

  /**
   * Form API callback. Make sure range element's default value is a string.
   *
   * @see \Drupal\Core\Render\Element\Range::valueCallback
   */
  public static function validateRange(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $value = $element['#value'];
    $value = ($value === 0) ? '0' : (string) $value;
    $form_state->setValueForElement($element, $value);
  }

}
