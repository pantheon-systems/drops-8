<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as UrlGenerator;
use Drupal\webform\Element\WebformLocation as WebformLocationElement;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an 'location' element.
 *
 * @WebformElement(
 *   id = "webform_location",
 *   label = @Translation("Location"),
 *   description = @Translation("Provides a form element to collect valid location information (address, longitude, latitude, geolocation) using Google's location auto completion API."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformLocation extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function getCompositeElements() {
    return WebformLocationElement::getCompositeElements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getInitializedCompositeElement(array &$element) {
    $form_state = new FormState();
    $form_completed = [];
    return WebformLocationElement::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'multiple' => FALSE,
      'title' => '',
      // General settings.
      'description' => '',
      'default_value' => [],
      // For display.
      'title_display' => '',
      'description_display' => '',
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      // Location settings.
      'geolocation' => FALSE,
      'hidden' => FALSE,
      'map' => FALSE,
      'api_key' => '',
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_items' => $this->getItemsDefaultFormat(),
    ] + $this->getDefaultBaseProperties();

    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      $properties[$composite_key . '__title'] = (string) $composite_element['#title'];
      if ($composite_key != 'value') {
        $properties[$composite_key . '__access'] = FALSE;
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);

    // Hide all composite elements by default.
    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      if ($composite_key != 'value' && !isset($element['#' . $composite_key . '__access'])) {
        $element['#' . $composite_key . '__access'] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'map' => $this->t('Map'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, $value, array $options = []) {
    // Return empty value.
    if (empty($value) || empty(array_filter($value))) {
      return '';
    }

    $format = $this->getItemFormat($element);
    if ($format == 'map') {
      $google_map_url = UrlGenerator::fromUri('http://maps.google.com/', ['query' => ['q' => $value['value']]]);

      $location = $value['location'];
      $key = (isset($element['#api_key'])) ? $element['#api_key'] : $this->configFactory->get('webform.settings')->get('elements.default_google_maps_api_key');
      $center = urlencode($value['location']);
      $image_map_uri = "https://maps.googleapis.com/maps/api/staticmap?zoom=14&size=600x338&markers=color:red%7C$location&key=$key&center=$center";

      return [
        'location' => [
          '#type' => 'link',
          '#title' => $value['value'],
          '#url' => $google_map_url,
          '#suffix' => '<br />',
        ],
        'map' => [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => $image_map_uri,
            '#width' => 600,
            '#height' => 338,
            '#alt' => $value['value'],
            '#attributes' => ['style' => "display: block; max-width: 100%; height: auto; border: 1px solid #ccc;"],
          ],
          '#url' => $google_map_url,
          '#suffix' => '<br />',
        ],
      ];
    }
    else {
      return parent::formatHtmlItems($element, $value, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Reverted #required label.
    $form['validation']['required']['#description'] = $this->t('Check this option if the user must enter a value.');

    $form['composite']['geolocation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use the browser's Geolocation as the default value."),
      '#description' => $this->t('The <a href="http://www.w3schools.com/html/html5_geolocation.asp">HTML Geolocation API</a> is used to get the geographical position of a user. Since this can compromise privacy, the position is not available unless the user approves it.'),
      '#return_value' => TRUE,
    ];
    $form['composite']['hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Hide the location element and collect the browser's Geolocation in the background."),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[geolocation]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['composite']['map'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display map'),
      '#description' => $this->t('Display a map for entered location.'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[hidden]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['composite']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#description' => $this->t('Google requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
    ];
    $default_api_key = \Drupal::config('webform.settings')->get('elements.default_google_maps_api_key');
    if ($default_api_key) {
      $form['composite']['api_key']['#description'] .= '<br/>' . $this->t('Defaults to: %value', ['%value' => $default_api_key]);
    }
    else {
      $form['composite']['api_key']['#required'] = TRUE;
      if (\Drupal::currentUser()->hasPermission('administer webform')) {
        $t_args = [':href' => UrlGenerator::fromRoute('webform.settings')->toString()];
        $form['composite']['api_key']['#description'] .= '<br/>' . $this->t('You can either enter an element specific API key here or set the <a href=":href">default site-wide API key</a>.', $t_args);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable() {
    $header = [
      $this->t('Key'),
      $this->t('Title'),
      $this->t('Visible'),
    ];

    $rows = [];
    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      $title = (isset($composite_element['#title'])) ? $composite_element['#title'] : $composite_key;
      $type = isset($composite_element['#type']) ? $composite_element['#type'] : NULL;
      $t_args = ['@title' => $title];
      $attributes = ['style' => 'width: 100%; margin-bottom: 5px'];

      $row = [];

      // Key.
      $row[$composite_key . '__key'] = [
        '#markup' => $composite_key,
        '#access' => TRUE,
      ];

      // Title, placeholder, and description.
      if ($type) {
        $row['title_and_description'] = [
          'data' => [
            $composite_key . '__title' => [
              '#type' => 'textfield',
              '#title' => $this->t('@title title', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => $this->t('Enter title...'),
              '#attributes' => $attributes,
            ],
          ],
        ];
      }
      else {
        $row['title_and_description'] = ['data' => ['']];
      }

      // Access.
      $row[$composite_key . '__access'] = [
        '#type' => 'checkbox',
        '#return_value' => TRUE,
      ];

      $rows[$composite_key] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
    ] + $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Use test values included in settings and not from
    // WebformCompositeBase::getTestValues.
    return FALSE;
  }

}
