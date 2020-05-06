<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Serialization\Json;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'tel' element.
 *
 * @WebformElement(
 *   id = "tel",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tel.php/class/Tel",
 *   label = @Translation("Telephone"), description = @Translation("Provides a form element for entering a telephone number."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Telephone extends TextBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
        'input_hide' => FALSE,
        'multiple' => FALSE,
        'international' => FALSE,
        'international_initial_country' => '',
        'international_preferred_countries' => [],
      ] + parent::defineDefaultProperties() + $this->defineDefaultMultipleProperties();
    // Add support for telephone_validation.module.
    if (\Drupal::moduleHandler()->moduleExists('telephone_validation')) {
      $properties += [
        'telephone_validation_format' => '',
        'telephone_validation_country' => '',
        'telephone_validation_countries' => [],
      ];
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['international_initial_country']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Add international library and classes.
    if (!empty($element['#international']) && $this->librariesManager->isIncluded('jquery.intl-tel-input')) {
      $element['#attached']['library'][] = 'webform/webform.element.telephone';

      $element['#attributes']['class'][] = 'js-webform-telephone-international';
      $element['#attributes']['class'][] = 'webform-webform-telephone-international';

      if (!empty($element['#international_initial_country'])) {
        $element['#attributes']['data-webform-telephone-international-initial-country'] = $element['#international_initial_country'];
      }
      if (!empty($element['#international_preferred_countries'])) {
        $element['#attributes']['data-webform-telephone-international-preferred-countries'] = Json::encode($element['#international_preferred_countries']);
      }

      // The utilsScript is fetched when the page has finished loading to
      // prevent blocking.
      // @see https://github.com/jackocnr/intl-tel-input
      $utils_script = '/libraries/jquery.intl-tel-input/build/js/utils.js';
      // Load utils.js from CDN defined in webform.libraries.yml.
      if (!file_exists(DRUPAL_ROOT . $utils_script)) {
        /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
        $library_discovery = \Drupal::service('library.discovery');
        $intl_tel_input_library = $library_discovery->getLibraryByName('webform', 'libraries.jquery.intl-tel-input');
        $cdn = reset($intl_tel_input_library['cdn']);
        $utils_script = $cdn . 'build/js/utils.js';
      }
      $element['#attached']['drupalSettings']['webform']['intlTelInput']['utilsScript'] = $utils_script;
    }

    // Add support for telephone_validation.module.
    if (\Drupal::moduleHandler()->moduleExists('telephone_validation')) {
      $format = $this->getElementProperty($element, 'telephone_validation_format');
      if ($format == \libphonenumber\PhoneNumberFormat::NATIONAL) {
        $country = (array) $this->getElementProperty($element, 'telephone_validation_country');
      }
      else {
        $country = $this->getElementProperty($element, 'telephone_validation_countries');
      }
      if ($format !== '') {
        $element['#element_validate'][] = [
          'Drupal\telephone_validation\Render\Element\TelephoneValidation',
          'validateTel',
        ];
        $element['#element_validate_settings'] = [
          'format' => $format,
          'country' => $country,
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['telephone'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Telephone settings'),
    ];
    $form['telephone']['international'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enhance support for international phone numbers'),
      '#description' => $this->t('Enhance the telephone element\'s international support using the jQuery <a href=":href">International Telephone Input</a> plugin.', [':href' => 'http://intl-tel-input.com/']),
      '#return_value' => TRUE,
    ];
    $form['telephone']['international_initial_country'] = [
      '#title' => $this->t('Initial country'),
      '#type' => 'select',
      '#empty_option' => $this->t('- None -'),
      '#options' => CountryManager::getStandardList(),
      '#states' => [
        'visible' => [':input[name="properties[international]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['telephone']['international_preferred_countries'] = [
      '#title' => $this->t('Preferred countries'),
      '#type' => 'select',
      '#options' => CountryManager::getStandardList(),
      '#description' => $this->t('Specify the countries to appear at the top of the list.'),
      '#select2' => TRUE,
      '#multiple' => TRUE,
      '#states' => [
        'visible' => [':input[name="properties[international]"]' => ['checked' => TRUE]],
      ],
    ];
    $this->elementManager->processElement($form['telephone']['international_preferred_countries']);

    if ($this->librariesManager->isExcluded('jquery.intl-tel-input')) {
      $form['telephone']['#access'] = FALSE;
      $form['telephone']['international']['#access'] = FALSE;
      $form['telephone']['international_initial_country']['#access'] = FALSE;
      $form['telephone']['international_preferred_countries']['#access'] = FALSE;
    }

    // Add support for telephone_validation.module.
    if (\Drupal::moduleHandler()->moduleExists('telephone_validation')) {
      $form['telephone']['telephone_validation_format'] = [
        '#type' => 'select',
        '#title' => $this->t('Valid format'),
        '#description' => $this->t('For international telephone numbers we suggest using <a href=":href">E164</a> format.', [':href' => 'https://en.wikipedia.org/wiki/E.164']),
        '#empty_option' => $this->t('- None -'),
        '#options' => [
          \libphonenumber\PhoneNumberFormat::E164 => $this->t('E164'),
          \libphonenumber\PhoneNumberFormat::NATIONAL => $this->t('National'),
        ],
      ];
      $form['telephone']['telephone_validation_country'] = [
        '#type' => 'select',
        '#title' => $this->t('Valid country'),
        '#options' => \Drupal::service('telephone_validation.validator')
          ->getCountryList(),
        '#states' => [
          'visible' => [
            ':input[name="properties[telephone_validation_format]"]' => ['value' => \libphonenumber\PhoneNumberFormat::NATIONAL],
          ],
          'required' => [
            ':input[name="properties[telephone_validation_format]"]' => ['value' => \libphonenumber\PhoneNumberFormat::NATIONAL],
          ],
        ],
      ];
      $form['telephone']['telephone_validation_countries'] = [
        '#type' => 'select',
        '#title' => $this->t('Valid countries'),
        '#description' => $this->t('If no country selected all countries are valid.'),
        '#options' => \Drupal::service('telephone_validation.validator')
          ->getCountryList(),
        '#select2' => TRUE,
        '#multiple' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[telephone_validation_format]"]' => ['value' => \libphonenumber\PhoneNumberFormat::E164],
          ],
        ],
      ];
      $this->elementManager->processElement($form['telephone']['telephone_validation_countries']);
    }
    elseif (\Drupal::currentUser()->hasPermission('administer modules')) {
      $t_args = [':href' => 'https://www.drupal.org/project/telephone_validation'];
      $form['telephone']['telephone_validation_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => $this->t('Install the <a href=":href">Telephone validation</a> module which provides international phone number validation.', $t_args),
        '#message_id' => 'webform.telephone_validation_message',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessageElement::STORAGE_STATE,
        '#access' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'link':
        $t_args = [':tel' => 'tel:' . $value, '@tel' => $value];
        return $this->t('<a href=":tel">@tel</a>', $t_args);

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
        'link' => $this->t('Link'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
        '#international' => TRUE,
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    if (empty($element['#international'])) {
      return FALSE;
    }
    return [
      '+1 212-333-4444',
      '+1 718-555-6666',
    ];
  }

}
