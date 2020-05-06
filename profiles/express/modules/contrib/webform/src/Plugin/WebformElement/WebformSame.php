<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_same' element.
 *
 * @WebformElement(
 *   id = "webform_same",
 *   label = @Translation("Same as…"),
 *   description = @Translation("Provides a form element for syncing the value of two elements."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformSame extends Checkbox {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'source' => '',
      'destination' => '',
      'destination_state' => 'visible',
    ] + parent::defineDefaultProperties();
    unset(
      $properties['required'],
      $properties['required_error']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form = parent::form($form, $form_state);

    // Get element's as options.
    // @todo Add more element types that should ignored.
    $ignored_types = [
      'webform_same',
    ];
    $flattened_elements = $webform->getElementsInitializedFlattenedAndHasValue();
    $options = [];
    foreach ($flattened_elements as $element_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      if (!in_array($element_plugin->getPluginId(), $ignored_types)) {
        $options[(string) $element_plugin->getPluginLabel()][$element_key] = $element['#admin_title'];
      }
    }
    ksort($options);

    $form['same'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Same as settings'),
      '#destination' => $this->t('Please note, the source and destination element must be the same element types.'),
    ];
    $form['same']['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Source element'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    $form['same']['destination'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination element'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    $form['same']['destination_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination state'),
      '#description' => $this->t("Determine how the destination element's state is toggled when 'Same as' is checked"),
      '#options' => [
        'visible' => $this->t('Show/hide'),
        'visible-slide' => $this->t('Slide in/out'),
      ],
      '#required' => TRUE,
    ];

    $form['same']['destination']['#element_validate'] = [[get_called_class(), 'validateDestination']];

    return $form;
  }

  /**
   * Form API callback. Validate webform same as destination.
   */
  public static function validateDestination(array &$element, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    // Get source and destination elements.
    $values = $form_state->getValues();
    $source = $values['properties']['source'];
    $source_element = $webform->getElement($source);
    $destination = $values['properties']['destination'];
    $destination_element = $webform->getElement($destination);

    if ($destination === $source) {
      $form_state->setError($element, t('The source and destination can not be the same element.'));
    }
    elseif ($destination_element['#type'] !== $source_element['#type']) {
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $element_plugin = $element_manager->getElementInstance($element);
      $t_args = [
        '@type' => $element_plugin->getPluginLabel(),
        '@source' => $source_element['#admin_title'],
        '@destination' => $destination_element['#admin_title'],
      ];
      $form_state->setError($element, t('The destination element (@destination) must be the same element type (@type) as source element (@source).', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    if (!isset($element['#source']) || !isset($element['#destination'])) {
      return;
    }

    // Get source element.
    $source = $element['#source'];
    $source_element = WebformElementHelper::getElement($form, $source);
    if (!$source_element) {
      return;
    }

    // Get destination element.
    $destination = $element['#destination'];
    $destination_element =& WebformElementHelper::getElement($form, $destination);
    if (!$destination_element) {
      return;
    }

    // Add #states to destination element.
    $selector = ':input[name="' . $element['#webform_key'] . '"]';
    $state = (!empty($element['#destination_state'])) ? $element['#destination_state'] : 'visible';
    $destination_element['#states'][$state][$selector] = ['checked' => FALSE];
    $destination_element['#states_clear'] = FALSE;

    // Track webform same elements and add validation callback used
    // to sync source to destination.
    $form += ['#webform_same' => []];
    $form['#webform_same'][$element['#webform_key']] = [
      'source' => $source,
      'destination' => $destination,
    ];
    $form['#validate'][] = [get_called_class(), 'validateForm'];
  }

  /**
   * Webform validation handler for webform same element.
   */
  public static function validateForm(&$form, FormStateInterface $form_state) {
    foreach ($form['#webform_same'] as $element_key => $settings) {
      if ($form_state->getValue($element_key)) {
        $form_state->setValue(
          $settings['destination'],
          $form_state->getValue($settings['source'])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    // Make sure destination is always sync'd when a webform submission
    // is saved.
    $webform = $webform_submission->getWebform();
    $same = $webform_submission->getElementData($element['#webform_key']);
    if (!$same) {
      return;
    }

    $source = $element['#source'];
    $destination = $element['#destination'];

    // Make sure source and destination elements exist.
    if (!$webform->getElement($source) || !$webform->getElement($destination)) {
      return;
    }

    // Sync source data with destination data.
    $source_data = $webform_submission->getElementData($source);
    $webform_submission->setElementData($destination, $source_data);
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->t('Billing address is the same as the shipping address'),
    ];
  }

}
