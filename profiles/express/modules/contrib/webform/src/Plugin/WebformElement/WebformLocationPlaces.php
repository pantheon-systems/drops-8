<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as UrlGenerator;

/**
 * Provides an 'location' element using Algolia Places.
 *
 * @WebformElement(
 *   id = "webform_location_places",
 *   label = @Translation("Location (Algolia Places)"),
 *   description = @Translation("Provides a form element to collect valid location information (address, longitude, latitude, geolocation) using Algolia Places."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformLocationPlaces extends WebformLocationBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'app_id' => '',
      'api_key' => '',
      'placeholder' => '',
      'geolocation' => FALSE,
      'hidden' => FALSE,
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->elementManager->isExcluded('webform_location_geocomplete') ? $this->t('Location') : parent::getPluginLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['composite']['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Algolia application id'),
      '#description' => $this->t('Algolia requires users to use a valid application id and API key for more than 1,000 requests per day. By <a href="https://www.algolia.com/users/sign_up/places">signing up</a>, you can create a free Places app and access your API keys.'),
    ];
    $default_app_id = \Drupal::config('webform.settings')->get('element.default_algolia_places_app_id');
    if ($default_app_id) {
      $form['composite']['app_id']['#description'] .= '<br /><br />' . $this->t('Defaults to: %value', ['%value' => $default_app_id]);
    }
    else {
      $form['composite']['app_id']['#required'] = TRUE;
      if (\Drupal::currentUser()->hasPermission('administer webform')) {
        $t_args = [':href' => UrlGenerator::fromRoute('webform.config.elements')->toString()];
        $form['composite']['app_id']['#description'] .= '<br /><br />' . $this->t('You can either enter an element specific application id and API key here or set the <a href=":href">default site-wide application id and API key</a>.', $t_args);
      }
    }
    $form['composite']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Algolia API key'),
    ];
    $default_api_key = \Drupal::config('webform.settings')->get('element.default_algolia_places_api_key');
    if ($default_api_key) {
      $form['composite']['api_key']['#description'] = $this->t('Defaults to: %value', ['%value' => $default_api_key]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [
      [
        'value' => '1600 Pennsylvania Avenue, Washington, District of Columbia, United States of America',
        'lat' => '38.8635',
        'lng' => '-76.946',
        'name' => '1600 Pennsylvania Avenue',
        'city' => 'Washington',
        'country' => 'United States of America',
        'country_code' => 'us',
        'administrative' => 'District of Columbia',
        'county' => 'Prince George\'s County',
        'suburb' => '',
        'postcode' => '20020',
      ],
    ];
  }

}
