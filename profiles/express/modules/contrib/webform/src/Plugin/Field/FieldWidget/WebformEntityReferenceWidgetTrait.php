<?php

namespace Drupal\webform\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\WebformInterface;

/**
 * Trait for webform entity reference and autocomplete widget.
 */
trait WebformEntityReferenceWidgetTrait {

  use WebformAjaxElementTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_data' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['default_data'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable default submission data (YAML)'),
      '#description' => t('If checked, site builders will be able to define default submission data (YAML)'),
      '#default_value' => $this->getSetting('default_data'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Default submission data: @default_data', ['@default_data' => $this->getSetting('default_data') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * Returns the target id element form for a single webform field widget.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements for a single widget for this field.
   */
  abstract protected function getTargetIdElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Set item default status to open.
    if (!isset($items[$delta]->status)) {
      $items[$delta]->status = WebformInterface::STATUS_OPEN;
    }

    // Get field name.
    $field_name = $items->getName();

    // Get field input name from field parents, field name, and the delta.
    $field_parents = array_merge($element['#field_parents'], [$field_name, $delta]);
    $field_input_name = (array_shift($field_parents)) . ('[' . implode('][', $field_parents) . ']');

    // Get target ID element.
    $target_id_element = $this->getTargetIdElement($items, $delta, $element, $form, $form_state);

    // Determine if this is a paragraph.
    $is_paragraph = ($items->getEntity()->getEntityTypeId() === 'paragraph');

    // Merge target ID and default element and set default #weight.
    // @see \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget::formElement
    $element = [
      'target_id' => $target_id_element + $element + ['#weight' => 0],
    ];

    // Get weight.
    $weight = $element['target_id']['#weight'];

    // Get webform.
    if ($form_state->isRebuilding()) {
      $user_input = $form_state->getUserInput();
      $target_id = $user_input[$field_name][$delta]['target_id'];
    }
    else {
      $target_id = $items[$delta]->target_id;
    }

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = ($target_id) ? Webform::load($target_id) : NULL;

    $element['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('@title settings', ['@title' => $element['target_id']['#title']]),
      '#element_validate' => [[$this, 'validateOpenClose']],
      '#open' => ($items[$delta]->target_id) ? TRUE : FALSE,
      '#weight' => $weight++,
    ];

    // Disable a warning message about the webform's state using Ajax
    $is_webform_closed = ($webform && $webform->isClosed());
    if ($is_webform_closed) {
      $t_args = [
        '%webform' => $webform->label(),
        ':href' => $webform->toUrl('settings-form')->toString(),
      ];
      if ($webform->access('update')) {
        $message = $this->t('The %webform webform is <a href=":href">closed</a>. The below status will be ignored.', $t_args);
      }
      else {
        $message = $this->t('The %webform webform is <strong>closed</strong>. The below status will be ignored.', $t_args);
      }
      $element['settings']['status_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $message,
      ];
    }
    else {
      // Render empty element so that Ajax wrapper is embedded in the page.
      $element['settings']['status_message'] = [];
    }
    $ajax_id = 'webform-entity-reference-' . $field_name . '-' . $delta;
    $this->buildAjaxElementTrigger($ajax_id, $element['target_id']);
    $this->buildAjaxElementUpdate($ajax_id, $element);
    $this->buildAjaxElementWrapper($ajax_id, $element['settings']['status_message']);

    $element['settings']['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#description' => $this->t('The open, closed, or scheduled status applies to only this webform instance.'),
      '#options' => [
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
        WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled'),
      ],
      '#options_display' => 'side_by_side',
      '#default_value' => $items[$delta]->status,
    ];

    $element['settings']['scheduled'] = [
      '#type' => 'item',
      '#title' => $element['target_id']['#title'],
      '#title_display' => 'invisible',
      '#input' => FALSE,
      '#states' => [
        'visible' => [
          'input[name="' . $field_input_name . '[settings][status]"]' => ['value' => WebformInterface::STATUS_SCHEDULED],
        ],
      ],
    ];
    $element['settings']['scheduled']['open'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Open'),
      '#default_value' => $items[$delta]->open ? DrupalDateTime::createFromTimestamp(strtotime($items[$delta]->open)) : NULL,
      '#prefix' => '<div class="container-inline form-item">',
      '#suffix' => '</div>',
      '#help' => FALSE,
      '#description' => [
        '#type' => 'webform_help',
        '#help' => $this->t('If the open date/time is left blank, this form will immediately be opened.'),
        '#help_title' => $this->t('Open'),
      ],
    ];
    $element['settings']['scheduled']['close'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Close'),
      '#default_value' => $items[$delta]->close ? DrupalDateTime::createFromTimestamp(strtotime($items[$delta]->close)) : NULL,
      '#prefix' => '<div class="container-inline form-item">',
      '#suffix' => '</div>',
      '#help' => FALSE,
      '#description' => [
        '#type' => 'webform_help',
        '#help' => $this->t('If the close date/time is left blank, this webform will never be closed.'),
        '#help_title' => $this->t('Close'),
      ],
    ];

    if ($this->getSetting('default_data')) {
      /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
      $token_manager = \Drupal::service('webform.token_manager');
      $token_types = ['webform', 'webform_submission'];

      $default_data_example = "# This is an example of a comment.
element_key: 'some value'

# The below example uses a token to get the current node's title.
# Add ':clear' to the end token to return an empty value when the token is missing.
title: '[webform_submission:node:title:clear]'
# The below example uses a token to get a field value from the current node.
full_name: '[webform_submission:node:field_full_name:clear]";
      if ($is_paragraph) {
        $token_types[] = 'paragraph';
        $default_data_example .= PHP_EOL . "# You can also use paragraphs tokens.
some_value: '[paragraph:some_value:clear]";
      }
      $element['settings']['default_data'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Default submission data (YAML)'),
        '#placeholder' => $this->t("Enter 'name': 'value' pairs…"),
        '#default_value' => $items[$delta]->default_data,
        '#webform_element' => TRUE,
        '#description' => [
          'content' => ['#markup' => $this->t('Enter submission data as name and value pairs as <a href=":href">YAML</a> which will be used to prepopulate the selected webform.', [':href' => 'https://en.wikipedia.org/wiki/YAML']), '#suffix' => ' '],
          'token' => $token_manager->buildTreeLink($token_types),
        ],
        '#more_title' => $this->t('Example'),
        '#more' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => $default_data_example,
        ],
      ];
      $element['settings']['token_tree_link'] = $token_manager->buildTreeElement($token_types);
      $token_manager->elementValidate($element['settings']['default_data'], $token_types);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    parent::massageFormValues($values, $form, $form_state);

    // Massage open/close dates.
    // @see \Drupal\webform\WebformEntitySettingsForm::save
    // @see \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase::massageFormValues
    foreach ($values as &$item) {
      $item += $item['settings'];
      unset($item['settings']);

      // Set default values.
      $item += [
        'target_id' => '',
        'default_data' => NULL,
        'status' => '',
        'open' => '',
        'close' => '',
      ];

      if ($item['status'] === WebformInterface::STATUS_SCHEDULED) {
        $states = ['open', 'close'];
        foreach ($states as $state) {
          if (!empty($item['scheduled'][$state]) && $item['scheduled'][$state] instanceof DrupalDateTime) {
            $item[$state] = WebformDateHelper::formatStorage($item['scheduled'][$state]);
          }
          else {
            $item[$state] = '';
          }
        }
      }
      else {
        $item['open'] = '';
        $item['close'] = '';
      }
      unset($item['scheduled']);
    }
    return $values;
  }

  /**
   * Validate callback to ensure that the open date <= the close date.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @see \Drupal\webform\WebformEntitySettingsForm::validateForm
   * @see \Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase::validateOpenClose
   */
  public function validateOpenClose(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $status = $element['status']['#value'];
    if ($status === WebformInterface::STATUS_SCHEDULED) {
      $open_date = $element['scheduled']['open']['#value']['object'];
      $close_date = $element['scheduled']['close']['#value']['object'];

      // Require open or close dates.
      if (empty($open_date) && empty($close_date)) {
        $form_state->setError($element['scheduled']['open'], $this->t('Please enter an open or close date'));
        $form_state->setError($element['scheduled']['close'], $this->t('Please enter an open or close date'));
      }

      // Make sure open date is not after close date.
      if ($open_date instanceof DrupalDateTime && $close_date instanceof DrupalDateTime) {
        if ($open_date->getTimestamp() !== $close_date->getTimestamp()) {
          $interval = $open_date->diff($close_date);
          if ($interval->invert === 1) {
            $form_state->setError($element['scheduled']['open'], $this->t('The @title close date cannot be before the open date', ['@title' => $element['#title']]));
          }
        }
      }
    }
  }

}
