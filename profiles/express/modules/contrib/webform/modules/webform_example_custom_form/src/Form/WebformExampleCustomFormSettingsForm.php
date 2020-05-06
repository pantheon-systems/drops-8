<?php

namespace Drupal\webform_example_custom_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example: Webform Custom (Configuration) Form configuration settings form.
 */
class WebformExampleCustomFormSettingsForm extends ConfigFormBase {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform element (plugin) manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->tokenManager = $container->get('webform.token_manager');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_example_custom_form_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform_example_custom_form.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_example_custom_form.settings');

    $form['#attributes'] = [
      'class' => [
        'webform-example-custom-form-settings',
      ],
    ];

    // Basic elements.
    $form['basic_elements'] = [
      '#type' => 'details',
      '#title' => 'Basic elements',
      '#open' => TRUE,
    ];
    $form['basic_elements']['textfield'] = [
      '#type' => 'textfield',
      '#title' => 'Text field',
      '#counter_type' => 'character',
      '#counter_maximum' => 10,
      '#default_value' => $config->get('textfield'),
    ];
    $form['basic_elements']['textarea'] = [
      '#type' => 'textarea',
      '#title' => 'Text area',
      '#counter_type' => 'word',
      '#counter_maximum' => 500,
      '#default_value' => $config->get('textarea'),
    ];
    $form['basic_elements']['select'] = [
      '#type' => 'select',
      '#title' => 'Select menu',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ],
      '#select2' => TRUE,
      '#default_value' => $config->get('select'),
    ];
    $form['basic_elements']['checkboxes'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes',
      '#options_display' => 'side_by_side',
      '#options_description_display' => 'help',
      '#options' => [
        'one' => 'One -- This is help text.',
        'two' => 'Two -- This is help text.',
        'three' => 'Three -- This is  help text.',
      ],
      '#default_value' => $config->get('checkboxes'),
    ];

    // Date elements.
    $form['date_elements'] = [
      '#type' => 'details',
      '#title' => 'Date elements',
      '#open' => TRUE,
    ];
    $form['date_elements']['date'] = [
      '#type' => 'date',
      '#title' => 'Date',
      '#default_value' => $config->get('date'),
    ];
    $form['date_elements']['datelist'] = [
      '#type' => 'datelist',
      '#title' => 'Date list',
      '#default_value' => $config->get('datelist'),
    ];
    $form['date_elements']['date_datepicker'] = [
      '#type' => 'date',
      '#title' => 'Date picker',
      '#datepicker' => TRUE,
      '#date_date_format' => 'D, m/d/Y',
      '#default_value' => $config->get('date_datepicker'),
    ];
    $form['date_elements']['webform_time'] = [
      '#type' => 'webform_time',
      '#title' => 'Time',
      '#default_value' => $config->get('webform_time'),
    ];

    // Advanced elements.
    $form['advanced_elements'] = [
      '#type' => 'details',
      '#title' => 'Advanced elements',
      '#open' => TRUE,
    ];
    $form['advanced_elements']['email_multiple'] = [
      '#type' => 'webform_email_multiple',
      '#title' => 'Email multiple',
      '#default_value' => $config->get('email_multiple'),
    ];
    $form['advanced_elements']['tel_international'] = [
      '#type' => 'tel',
      '#title' => 'Telephone (International)',
      '#international' => TRUE,
      '#telephone_validation_format' => '0',
      '#default_value' => $config->get('tel_international'),
    ];
    $form['advanced_elements']['range'] = [
      '#type' => 'range',
      '#title' => 'Range',
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#output' => 'right',
      '#output__field_prefix' => '$',
      '#output__field_suffix' => '.00',
      '#default_value' => $config->get('range'),
    ];
    $form['advanced_elements']['managed_file'] = [
      '#type' => 'managed_file',
      '#title' => 'File upload',
      '#default_value' => $config->get('managed_file'),
    ];
    $form['advanced_elements']['tableselect'] = [
      '#type' => 'tableselect',
      '#title' => 'Table select',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ],
      '#default_value' => $config->get('tableselect'),
    ];
    $form['advanced_elements']['webform_tableselect_sort'] = [
      '#type' => 'webform_tableselect_sort',
      '#title' => 'Tableselect sort',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
      ],
      '#default_value' => $config->get('webform_tableselect_sort'),
    ];
    $form['advanced_elements']['webform_table_sort'] = [
      '#type' => 'webform_table_sort',
      '#title' => 'Table sort',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ],
      '#default_value' => $config->get('webform_table_sort'),
    ];
    $form['advanced_elements']['webform_autocomplete'] = [
      '#type' => 'webform_autocomplete',
      '#title' => 'Autocomplete',
      '#autocomplete_items' => 'country_names',
      '#default_value' => $config->get('webform_autocomplete'),
    ];
    $form['advanced_elements']['webform_buttons'] = [
      '#type' => 'webform_buttons',
      '#title' => 'Buttons',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ],
      '#default_value' => $config->get('webform_buttons'),
    ];
    $form['advanced_elements']['webform_codemirror'] = [
      '#type' => 'webform_codemirror',
      '#title' => 'CodeMirror',
      '#mode' => 'yaml',
      '#default_value' => $config->get('webform_codemirror'),
    ];
    $form['advanced_elements']['webform_image_select'] = [
      '#type' => 'webform_image_select',
      '#title' => 'Image select',
      '#show_label' => TRUE,
      '#images' => [
        'kitten_1' => [
          'text' => 'Cute Kitten 1',
          'src' => 'http://placekitten.com/220/200',
        ],
        'kitten_2' => [
          'text' => 'Cute Kitten 2',
          'src' => 'http://placekitten.com/180/200',
        ],
        'kitten_3' => [
          'text' => 'Cute Kitten 3',
          'src' => 'http://placekitten.com/130/200',
        ],
      ],
      '#default_value' => $config->get('webform_image_select'),
    ];
    $form['advanced_elements']['webform_rating'] = [
      '#type' => 'webform_rating',
      '#title' => 'Rating',
      '#default_value' => $config->get('webform_rating'),
    ];
    $form['advanced_elements']['webform_terms_of_service'] = [
      '#type' => 'webform_terms_of_service',
      '#terms_content' => 'These are the terms of service.',
      '#default_value' => $config->get('webform_terms_of_service'),
    ];
    $form['advanced_elements']['webform_likert'] = [
      '#type' => 'webform_likert',
      '#title' => 'Likert',
      '#questions' => [
        'q1' => 'Please answer question 1?',
        'q2' => 'How about now answering question 2?',
        'q3' => 'Finally, here is question 3?',
      ],
      '#answers' => [
        1,
        2,
        3,
        4,
        5,
      ],
      '#default_value' => $config->get('webform_likert'),
    ];

    // Entity reference elements.
    $form['entity_reference_elements'] = [
      '#type' => 'details',
      '#title' => 'Entity reference elements',
      '#open' => TRUE,
    ];
    $form['entity_reference_elements']['entity_autocomplete'] = [
      '#type' => 'entity_autocomplete',
      '#title' => 'Entity autocomplete',
      '#target_type' => 'user',
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
      '#default_value' => $config->get('entity_autocomplete'),
    ];
    $form['entity_reference_elements']['webform_entity_select'] = [
      '#type' => 'webform_entity_select',
      '#title' => 'Entity select',
      '#target_type' => 'user',
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
      '#default_value' => $config->get('webform_entity_select'),
    ];
    $form['entity_reference_elements']['webform_term_select'] = [
      '#type' => 'webform_term_select',
      '#title' => 'Term select',
      '#vocabulary' => 'tags',
      '#default_value' => $config->get('webform_term_select'),
    ];

    // Webform composites.
    $form['webform_composites'] = [
      '#type' => 'details',
      '#title' => 'Webform composites',
      '#open' => TRUE,
    ];
    $form['webform_composites']['webform_custom_composite'] = [
      '#type' => 'webform_custom_composite',
      '#title' => 'Custom composite',
      '#element' => [
        'first_name' => [
          '#type' => 'textfield',
          '#title' => 'First name',
        ],
        'last_name' => [
          '#type' => 'textfield',
          '#title' => 'Last name',
        ],
        'gender' => [
          '#type' => 'webform_select_other',
          '#options' => 'gender',
          '#title' => 'Gender',
        ],
        'martial_status' => [
          '#type' => 'webform_select_other',
          '#options' => 'marital_status',
          '#title' => 'Martial status',
        ],
        'employment_status' => [
          '#type' => 'webform_select_other',
          '#options' => 'employment_status',
          '#title' => 'Employment status',
        ],
        'age' => [
          '#type' => 'number',
          '#title' => 'Age',
        ],
      ],
      '#default_value' => $config->get('webform_custom_composite'),
    ];

    // Form elements.
    $form['form_elements'] = [
      '#type' => 'details',
      '#title' => 'Form elements',
      '#open' => TRUE,
    ];
    $form['form_elements']['form_element_input_mask'] = [
      '#type' => 'textfield',
      '#title' => 'Form element (Input mask: Phone)',
      '#input_mask' => '(999) 999-9999',
      '#test' => '',
      '#default_value' => $config->get('form_element_input_mask'),
    ];
    $form['form_elements']['form_element_input_hide'] = [
      '#type' => 'textfield',
      '#title' => 'Form element (Input hiding)',
      '#input_hide' => TRUE,
      '#default_value' => $config->get('form_element_input_hide'),
    ];
    $form['form_elements']['form_element_descriptions'] = [
      '#type' => 'textfield',
      '#title' => 'Form element (Labels and descriptions)',
      '#description' => 'This is a description.',
      '#placeholder' => 'This is a placeholder.',
      '#help' => 'This is help.',
      '#more' => 'This is more text',
      '#default_value' => $config->get('form_element_descriptions'),
    ];

    // Dividers.
    $form['dividers'] = [
      '#type' => 'details',
      '#title' => 'Dividers',
      '#open' => TRUE,
    ];
    $form['dividers']['horizontal_rule_dotted_medium'] = [
      '#type' => 'webform_horizontal_rule',
      '#attributes' => [
        'class' => [
          'webform-horizontal-rule--dotted',
          'webform-horizontal-rule--medium',
        ],
      ],
    ];

    // Messages.
    $form['messages'] = [
      '#type' => 'details',
      '#title' => 'Messages',
      '#open' => TRUE,
    ];
    $form['messages']['message_info'] = [
      '#type' => 'webform_message',
      '#message_type' => 'info',
      '#message_message' => 'This is an <strong>info</strong> message.',
      '#message_close' => TRUE,
    ];

    // Flexbox.
    $form['flexbox'] = [
      '#type' => 'details',
      '#title' => 'Flexbox',
      '#open' => TRUE,
    ];
    $form['flexbox']['webform_flexbox'] = [
      '#type' => 'webform_flexbox',
      '#title' => 'Flexbox elements',
    ];
    $form['flexbox']['webform_flexbox']['element_flex_1'] = [
      '#type' => 'textfield',
      '#title' => 'Element (Flex: 1)',
      '#flex' => 1,
      '#default_value' => $config->get('element_flex_1'),
    ];
    $form['flexbox']['webform_flexbox']['element_flex_2'] = [
      '#type' => 'textfield',
      '#title' => 'Element (Flex: 2)',
      '#flex' => 2,
      '#default_value' => $config->get('element_flex_2'),
    ];
    $form['flexbox']['webform_flexbox']['element_flex_3'] = [
      '#type' => 'textfield',
      '#title' => 'Element (Flex: 3)',
      '#flex' => 3,
      '#default_value' => $config->get('element_flex_3'),
    ];

    // Internal.
    $form['internal'] = [
      '#type' => 'details',
      '#title' => 'Internal',
      '#open' => TRUE,
    ];
    $form['internal']['checkbox_value'] = [
      '#type' => 'webform_checkbox_value',
      '#title' => 'Checkbox with value',
      '#value__title' => 'Enter a value',
      '#default_value' => $config->get('checkbox_value'),
    ];
    $form['internal']['mapping'] = [
      '#type' => 'webform_mapping',
      '#title' => 'Mapping',
      '#source' => [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ],
      '#destination' => [
        'four' => 'Four',
        'five' => 'Five',
        'six' => 'Six',
      ],
      '#default_value' => $config->get('mapping'),
    ];
    $form['internal']['multiple'] = [
      '#type' => 'webform_multiple',
      '#title' => 'Multiple values',
      '#element' => [
        'first_name' => [
          '#type' => 'textfield',
          '#title' => 'first_name',
        ],
        'last_name' => [
          '#type' => 'textfield',
          '#title' => 'last_name',
        ],
      ],
      '#default_value' => $config->get('multiple'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#tree' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save configuration',
      '#button_type' => 'primary',
    ];

    // Process elements.
    $this->elementManager->processElements($form);

    // Replace tokens.
    $form = $this->tokenManager->replace($form);

    // Attach the webform library.
    $form['#attached']['library'][] = 'webform/webform.form';

    // Autofocus: Save details open/close state.
    $form['#attributes']['class'][] = 'js-webform-autofocus';
    $form['#attached']['library'][] = 'webform/webform.form.auto_focus';

    // Unsaved: Warn users about unsaved changes.
    $form['#attributes']['class'][] = 'js-webform-unsaved';
    $form['#attached']['library'][] = 'webform/webform.form.unsaved';

    // Details save: Attach details element save open/close library.
    $form['#attached']['library'][] = 'webform/webform.element.details.save';

    // Details toggle: Display collapse/expand all details link.
    $form['#attributes']['class'][] = 'js-webform-details-toggle';
    $form['#attributes']['class'][] = 'webform-details-toggle';
    $form['#attached']['library'][] = 'webform/webform.element.details.toggle';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get all values.
    $values = $form_state->getValues();

    // Remove Form API values.
    unset(
      $values['form_build_id'],
      $values['form_token'],
      $values['form_id'],
      $values['op'],
      $values['actions']
    );

    // Save config.
    $this->config('webform_example_custom_form.settings')
      ->setData($values)
      ->save();

    // Display message.
    parent::submitForm($form, $form_state);
  }

}
