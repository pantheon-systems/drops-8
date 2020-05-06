<?php

namespace Drupal\webform_location_geocomplete\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as UrlGenerator;
use Drupal\webform\Plugin\WebformElement\WebformLocationBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an 'location' element using Geocomplete.
 *
 * @WebformElement(
 *   id = "webform_location_geocomplete",
 *   label = @Translation("Location (Geocomplete)"),
 *   description = @Translation("Provides a form element to collect valid location information (address, longitude, latitude, geolocation) using Google Maps API's Geocoding and Places Autocomplete."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 *   deprecated = TRUE,
 *   deprecated_message = @Translation("The jQuery: Geocoding and Places Autocomplete Plugin library is not being maintained. It has been <a href=""https://www.drupal.org/node/2991275"">deprecated</a> and will be removed before Webform 8.x-5.0."),
 * )
 */
class WebformLocationGeocomplete extends WebformLocationBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'geolocation' => FALSE,
      'hidden' => FALSE,
      'map' => FALSE,
      'api_key' => '',
    ] + parent::defineDefaultProperties()
      + $this->defineDefaultBaseProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->elementManager->isExcluded('webform_location_places') ? $this->t('Location') : parent::getPluginLabel();
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // Return empty value.
    if (empty($value) || empty(array_filter($value))) {
      return '';
    }

    $format = $this->getItemFormat($element);
    if ($format == 'map') {
      $google_map_url = UrlGenerator::fromUri('http://maps.google.com/', ['query' => ['q' => $value['value']]]);

      $location = $value['location'];
      $key = (isset($element['#api_key'])) ? $element['#api_key'] : $this->configFactory->get('webform.settings')->get('element.default_google_maps_api_key');
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
      return parent::formatHtmlItem($element, $webform_submission, $options);
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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

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
    $default_api_key = \Drupal::config('webform.settings')->get('element.default_google_maps_api_key');
    if ($default_api_key) {
      $form['composite']['api_key']['#description'] .= '<br /><br />' . $this->t('Defaults to: %value', ['%value' => $default_api_key]);
    }
    else {
      $form['composite']['api_key']['#required'] = TRUE;
      if (\Drupal::currentUser()->hasPermission('administer webform')) {
        $t_args = [':href' => UrlGenerator::fromRoute('webform.config.elements')->toString()];
        $form['composite']['api_key']['#description'] .= '<br /><br />' . $this->t('You can either enter an element specific API key here or set the <a href=":href">default site-wide API key</a>.', $t_args);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#map' => TRUE,
      '#geolocation' => TRUE,
      '#format' => 'map',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [
      ['value' => 'The White House, 1600 Pennsylvania Ave NW, Washington, DC 20500, USA'],
      ['value' => 'London SW1A 1AA, United Kingdom'],
      ['value' => 'Moscow, Russia, 10307'],
    ];
  }

}
